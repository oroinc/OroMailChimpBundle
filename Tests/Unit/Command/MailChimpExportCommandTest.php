<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MailChimpBundle\Command\MailChimpExportCommand;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class MailChimpExportCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeRunEveryFiveMinutes(): void
    {
        $command = new MailChimpExportCommand(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(MessageProducerInterface::class)
        );

        self::assertEquals('*/5 * * * *', $command->getDefaultDefinition());
    }
}
