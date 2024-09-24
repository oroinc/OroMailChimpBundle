<?php

namespace Oro\Bundle\MailChimpBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Email campaign ACL voter class.
 */
class EmailCampaignVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = ['EDIT'];

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isEmailCampaignSent($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isEmailCampaignSent(int $entityId): bool
    {
        $emailCampaign = $this->doctrineHelper
            ->getEntityRepository($this->className)
            ->find($entityId);

        if ($emailCampaign) {
            return $emailCampaign->isSent();
        }

        return false;
    }
}
