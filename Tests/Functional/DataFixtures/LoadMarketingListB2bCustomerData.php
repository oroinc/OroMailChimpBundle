<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadB2bCustomerEmailData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadMarketingListB2bCustomerData extends LoadMarketingListData
{
    protected array $mlData = [
        [
            'type' => 'dynamic',
            'name' => 'Test B2bCustomer ML',
            'description' => '',
            'entity' => B2bCustomer::class,
            'reference' => 'mailchimp:ml_b2b_customer',
            'segment' => 'mailchimp:ml_b2b_customer:segment',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadB2bCustomerEmailData::class,
            LoadSegmentB2bCustomerData::class,
            LoadUser::class
        ];
    }
}
