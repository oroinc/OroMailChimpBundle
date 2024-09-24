<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Strategy;

use Monolog\Logger;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy as BaseStrategy;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Mailchimp member export strategy.
 */
class MemberExportStrategy extends BaseStrategy implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected int $logLevel = Logger::INFO;

    #[\Override]
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogLevel(int $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * @param Member|object $entity
     * @return Member|null
     */
    #[\Override]
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        /** @var Member $entity */
        $entity = $this->beforeProcessEntity($entity);
        $entity = $this->processEntity($entity);
        $entity = $this->afterProcessEntity($entity);

        $this->logger?->log($this->logLevel, sprintf('Exporting MailChimp Member [id=%s]', $entity->getId()));

        return $entity;
    }

    /**
     * @param Member $member
     * @return Member
     */
    protected function processEntity(Member $member)
    {
        $member->setSubscribersList(
            $this->databaseHelper->getEntityReference($member->getSubscribersList())
        );
        /** @var Channel $channel */
        $channel = $this->databaseHelper->getEntityReference($member->getChannel());
        $member->setChannel($channel);

        return $member;
    }
}
