<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ChannelBundle\Entity\Channel;
use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Migrations\Data\ORM\DefaultChannelData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadB2bChannelData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $channel = $this->container->get('oro_channel.builder.factory')
            ->createBuilder()
            ->setStatus(Channel::STATUS_ACTIVE)
            ->setEntities([B2bCustomer::class])
            ->setChannelType(DefaultChannelData::B2B_CHANNEL_TYPE)
            ->setName('Test Sales channel')
            ->getChannel();

        $manager->persist($channel);
        $manager->flush();

        $manager->flush();
    }
}
