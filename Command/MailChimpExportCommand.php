<?php

declare(strict_types=1);

namespace Oro\Bundle\MailChimpBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exports members and static segments to MailChimp.
 */
class MailChimpExportCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    protected static $defaultName = 'oro:cron:mailchimp:export';

    private ManagerRegistry $doctrine;
    private MessageProducerInterface $messageProducer;

    public function __construct(ManagerRegistry $doctrine, MessageProducerInterface $messageProducer)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->messageProducer = $messageProducer;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '*/5 * * * *';
    }

    #[\Override]
    public function isActive(): bool
    {
        return ($this->getStaticSegmentRepository()->countStaticSegments() > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addOption(
                'segments',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'MailChimp static segment IDs to sync'
            )->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force synchronization of segments regardless of their sync status'
            )
            ->setDescription('Exports members and static segments to MailChimp.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command exports members and static segments that require synchronization to MailChimp.

  <info>php %command.full_name%</info>

The <info>--segments</info> option can be used to limit the export only to the specified static segments:

  <info>php %command.full_name% --segments=<ID1> --segments=<ID2> --segments=<IDN></info>

The <info>--force</info> option will force synchronization of segments regardless of their sync status:

  <info>php %command.full_name% --force</info>

HELP
            )
            ->addUsage('--segments=<ID1> --segments=<ID2> --segments=<IDN>')
            ->addUsage('--force')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $segments = $input->getOption('segments');
        $force = (bool) $input->getOption('force');

        /** @var StaticSegment[] $iterator */
        $iterator = $this->getStaticSegmentRepository()->getStaticSegmentsToSync($segments, null, $force);

        /** @var Integration[] $integrationToSync */
        $integrationToSync   = [];
        $integrationSegments = [];
        foreach ($iterator as $staticSegment) {
            $integration = $staticSegment->getChannel();
            $userId = $staticSegment->getMarketingList()->getOwner()->getId();
            $integrationToSync[$integration->getId()] = $integration;
            $integrationSegments[$integration->getId()][$userId][] = $staticSegment->getId();
        }
        if (count($integrationToSync) > 0) {
            $output->writeln('Send export MailChimp message for integration:');
            foreach ($integrationToSync as $integration) {
                $integrationId = $integration->getId();
                foreach ($integrationSegments[$integrationId] as $userId => $segmentIds) {
                    $output->writeln(sprintf(
                        'Integration "%s" user "%s" and segments "%s"',
                        $integrationId,
                        $userId,
                        implode('", "', $segmentIds)
                    ));

                    $this->messageProducer->send(ExportMailchimpSegmentsTopic::getName(), [
                        'integrationId' => $integrationId,
                        'segmentsIds' => $segmentIds,
                        'userId' => $userId,
                    ]);
                }
            }
        } else {
            $output->writeln('Active MailChimp Integrations not found.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getStaticSegmentRepository(): StaticSegmentRepository
    {
        return $this->doctrine->getRepository(StaticSegment::class);
    }
}
