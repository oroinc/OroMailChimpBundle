<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\MergeVar;

use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVar;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarFields;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarFieldsInterface;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarInterface;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarProvider;

class MergeVarProviderTest extends \PHPUnit\Framework\TestCase
{
    private MergeVarProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new MergeVarProvider();
    }

    public function testGetMergeVarsFieldsWorks()
    {
        $mergeVarConfig = [['name' => 'foo', 'type' => 'email'], ['name' => 'bar', 'type' => 'text']];

        $subscribersList = new SubscribersList();
        $subscribersList->setMergeVarConfig($mergeVarConfig);

        $mergeVarFields = $this->provider->getMergeVarFields($subscribersList);

        $this->assertEquals(
            new MergeVarFields([new MergeVar($mergeVarConfig[0]), new MergeVar($mergeVarConfig[1])]),
            $mergeVarFields
        );

        $this->assertSame($mergeVarFields, $subscribersList->getMergeVarFields());
    }

    public function testGetMergeVarsFieldsWithEmptyConfigWorks()
    {
        $subscribersList = new SubscribersList();
        $subscribersList->setMergeVarConfig([]);

        $mergeVarFields = $this->provider->getMergeVarFields($subscribersList);

        $this->assertEquals(
            new MergeVarFields([]),
            $mergeVarFields
        );

        $this->assertSame($mergeVarFields, $subscribersList->getMergeVarFields());
    }

    public function testAssignMergeVarValuesWorks()
    {
        $emailField = $this->createMock(MergeVarInterface::class);
        $emailField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Email Address');

        $phoneField = $this->createMock(MergeVarInterface::class);
        $phoneField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Phone');

        $firstNameField = $this->createMock(MergeVarInterface::class);
        $firstNameField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('First Name');

        $lastNameField = $this->createMock(MergeVarInterface::class);
        $lastNameField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Last Name');

        $mergeVarFields = $this->createMock(MergeVarFieldsInterface::class);
        $mergeVarFields->expects($this->once())
            ->method('getEmail')
            ->willReturn($emailField);

        $mergeVarFields->expects($this->once())
            ->method('getPhone')
            ->willReturn($phoneField);

        $mergeVarFields->expects($this->once())
            ->method('getFirstName')
            ->willReturn($firstNameField);

        $mergeVarFields->expects($this->once())
            ->method('getLastName')
            ->willReturn($lastNameField);

        $email = 'test@example.com';
        $phone = '333-555-7777';
        $firstName = 'John';
        $lastName = 'Doe';

        $member = new Member();
        $member->setMergeVarValues(
            [
                'Email Address' => $email,
                'Phone' => $phone,
                'First Name' => $firstName,
                'Last Name' => $lastName,
            ]
        );
        $this->provider->assignMergeVarValues($member, $mergeVarFields);

        $this->assertEquals($email, $member->getEmail());
        $this->assertEquals($phone, $member->getPhone());
        $this->assertEquals($firstName, $member->getFirstName());
        $this->assertEquals($lastName, $member->getLastName());
    }

    public function testAssignMergeVarValuesWorksWithEmptyValues()
    {
        $emailField = $this->createMock(MergeVarInterface::class);
        $emailField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Email Address');

        $phoneField = $this->createMock(MergeVarInterface::class);
        $phoneField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Phone');

        $firstNameField = $this->createMock(MergeVarInterface::class);
        $firstNameField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('First Name');

        $lastNameField = $this->createMock(MergeVarInterface::class);
        $lastNameField->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Last Name');

        $mergeVarFields = $this->createMock(MergeVarFieldsInterface::class);
        $mergeVarFields->expects($this->once())
            ->method('getEmail')
            ->willReturn($emailField);

        $mergeVarFields->expects($this->once())
            ->method('getPhone')
            ->willReturn($phoneField);

        $mergeVarFields->expects($this->once())
            ->method('getFirstName')
            ->willReturn($firstNameField);

        $mergeVarFields->expects($this->once())
            ->method('getLastName')
            ->willReturn($lastNameField);

        $member = new Member();
        $member->setMergeVarValues([]);
        $this->provider->assignMergeVarValues($member, $mergeVarFields);

        $this->assertNull($member->getEmail());
        $this->assertNull($member->getPhone());
        $this->assertNull($member->getFirstName());
        $this->assertNull($member->getLastName());
    }

    public function testAssignMergeVarValuesWorksWithEmptyFields()
    {
        $mergeVarFields = $this->createMock(MergeVarFieldsInterface::class);
        $mergeVarFields->expects($this->once())
            ->method('getEmail')
            ->willReturn(null);

        $mergeVarFields->expects($this->once())
            ->method('getPhone')
            ->willReturn(null);

        $mergeVarFields->expects($this->once())
            ->method('getFirstName')
            ->willReturn(null);

        $mergeVarFields->expects($this->once())
            ->method('getLastName')
            ->willReturn(null);

        $email = 'test@example.com';
        $phone = '333-555-7777';
        $firstName = 'John';
        $lastName = 'Doe';

        $member = new Member();
        $member->setMergeVarValues(
            [
                'Email Address' => $email,
                'Phone' => $phone,
                'First Name' => $firstName,
                'Last Name' => $lastName,
            ]
        );
        $this->provider->assignMergeVarValues($member, $mergeVarFields);

        $this->assertNull($member->getEmail());
        $this->assertNull($member->getPhone());
        $this->assertNull($member->getFirstName());
        $this->assertNull($member->getLastName());
    }
}
