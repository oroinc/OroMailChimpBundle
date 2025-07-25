<?php

namespace Oro\Bundle\MailChimpBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is responsible for synchronizing all members from MailChimp.
 * It updates their statuses and ensures that local data is synchronized with MailChimp.
 */
#[AsCommand(
    name: 'oro:mailchimp:force-import:members',
    description: 'Import members from MailChimp.'
)]
class MailchimpImportMembersCommand extends Command
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $messageProducer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', 0, InputOption::VALUE_REQUIRED, 'MailChimp channel id')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> is not meant to be used regularly and is used to import all Mailchimp members.

  <info>php %command.full_name% --channel=<ID1></info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelId = (int) $input->getOption('channel');
        if (!$this->getChannel($channelId)) {
            $output->writeln(sprintf('MailChimp Integrations with id "%s" not found or disabled.', $channelId));

            return self::FAILURE;
        }

        // Send a message to the queue to trigger MailChimp Member import for the specified channel.
        // The “force” parameter indicates that all members should be synchronized, regardless of the time of the
        // last synchronization.
        $this->messageProducer->send(SyncIntegrationTopic::getName(), [
            'integration_id' => $channelId,
            'connector' => 'member',
            'connector_parameters' => ['force' => true]
        ]);

        $output->writeln(sprintf('MailChimp member sync has been scheduled for integration ID "%s"', $channelId));

        return self::SUCCESS;
    }

    private function getChannel(int $channelId): ?Channel
    {
        return $this->doctrine->getRepository(Channel::class)->findOneBy([
            'id' => $channelId,
            'type' => 'mailchimp',
            'enabled' => true,
        ]);
    }
}
