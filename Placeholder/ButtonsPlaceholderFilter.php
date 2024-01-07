<?php

namespace Oro\Bundle\MailChimpBundle\Placeholder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Provider\ChannelType;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

/**
 * Availability resolver for connect marketing list to mailchimp action button on marketing list view page.
 */
class ButtonsPlaceholderFilter
{
    /**
     * @var ContactInformationFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ContactInformationFieldsProvider $fieldsProvider, ManagerRegistry $registry)
    {
        $this->fieldsProvider = $fieldsProvider;
        $this->registry = $registry;
    }

    /**
     * @param mixed $entity
     * @return bool
     */
    public function isApplicable($entity)
    {
        if ($entity instanceof MarketingList) {
            if (!$this->hasMailChimpIntegration()) {
                return false;
            }

            return (bool)$this->fieldsProvider->getMarketingListTypedFields(
                $entity,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            );
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function hasMailChimpIntegration()
    {
        return (bool)$this->registry->getRepository(Channel::class)
            ->getConfiguredChannelsForSync(ChannelType::TYPE, true);
    }
}
