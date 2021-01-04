<?php
declare(strict_types=1);

namespace Oro\Bundle\MailChimpBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\MailChimpBundle\Async\Topics;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Model\StaticSegment\StaticSegmentsMemberStateManager;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Exports members and static segments to MailChimp.
 */
class MailChimpExportCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var string */
    protected static $defaultName = 'oro:cron:mailchimp:export';

    protected JobExecutor $jobExecutor;
    protected StaticSegmentsMemberStateManager $reverseSyncProcessor;
    protected StaticSegmentsMemberStateManager $staticSegmentStateManager;
    protected DoctrineHelper $doctrineHelper;

    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    public function isActive()
    {
        return ($this->getStaticSegmentRepository()->countStaticSegments() > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
    public function execute(InputInterface $input, OutputInterface $output)
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
            $integrationToSync[$integration->getId()] = $integration;
            $integrationSegments[$integration->getId()][] = $staticSegment->getId();
        }
        if (count($integrationToSync) > 0) {
            $output->writeln('Send export MailChimp message for integration:');
            foreach ($integrationToSync as $integration) {
                $message = new Message();
                $message->setPriority(MessagePriority::VERY_LOW);
                $message->setBody([
                    'integrationId' => $integration->getId(),
                    'segmentsIds' => $integrationSegments[$integration->getId()]
                ]);

                $output->writeln(sprintf(
                    'Integration "%s" and segments "%s"',
                    $integration->getId(),
                    implode('", "', $message->getBody()['segmentsIds'])
                ));

                $messageProducer = $this->container->get('oro_message_queue.message_producer');
                $messageProducer->send(Topics::EXPORT_MAILCHIMP_SEGMENTS, $message);
            }
        } else {
            $output->writeln('Active MailChimp Integrations not found.');
        }
    }

    protected function getStaticSegmentRepository(): StaticSegmentRepository
    {
        /** @var RegistryInterface $registry */
        $registry = $this->container->get('doctrine');

        return $registry->getRepository(StaticSegment::class);
    }
}
