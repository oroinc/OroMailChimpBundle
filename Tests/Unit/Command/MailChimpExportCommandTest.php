<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\MailChimpBundle\Command\MailChimpExportCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailChimpExportCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithoutArgumentsAndImplementsNecessaryInterfaces()
    {
        $command = new MailChimpExportCommand();
        static::assertInstanceOf(Command::class, $command);
        static::assertInstanceOf(ContainerAwareInterface::class, $command);
        static::assertInstanceOf(CronCommandInterface::class, $command);
    }

    public function testShouldBeRunEveryFiveMinutes()
    {
        $command = new MailChimpExportCommand();

        self::assertEquals('*/5 * * * *', $command->getDefaultDefinition());
    }

    public function testShouldAllowSetContainer()
    {
        $command = new class(MailChimpExportCommand::getDefaultName()) extends MailChimpExportCommand {
            public function xgetContainer(): ContainerInterface
            {
                return $this->container;
            }
        };

        $container = new Container();
        $command->setContainer($container);

        static::assertSame($container, $command->xgetContainer());
    }
}
