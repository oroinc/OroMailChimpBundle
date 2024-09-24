<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\ImportExport;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\ImportExport\Reader\StaticSegmentReader;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadStaticSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class MemberSyncWriterTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadStaticSegmentData::class]);
    }

    public function testWrite(): void
    {
        $segment = $this->getReference('mailchimp:segment_one');

        $this->memberSyncWrite([$segment]);

        $memberRepository = $this->getContainer()->get('doctrine')->getRepository(Member::class);
        /** @var Member[] $members */
        $members = $memberRepository->findBy([], ['email' => 'ASC']);
        self::assertCount(2, $members);

        self::assertEquals('member1@example.com', $members[0]->getEmail());
        self::assertEquals("Daniel\tst", $members[0]->getFirstName());
        self::assertEquals(
            'Case <a href="https://www.goo.com/search?q=json&oq=json&aqs=chrome..69">Дж.[s`ón]</a>',
            $members[0]->getLastName()
        );
        self::assertEquals(
            [
                'EMAIL' => 'member1@example.com',
                'FNAME' => "Daniel\tst",
                'LNAME' => 'Case <a href="https://www.goo.com/search?q=json&oq=json&aqs=chrome..69">Дж.[s`ón]</a>'
            ],
            $members[0]->getMergeVarValues()
        );

        self::assertEquals('member2@example.com', $members[1]->getEmail());
        self::assertEquals('John', $members[1]->getFirstName());
        self::assertEquals('Case', $members[1]->getLastName());
        self::assertEquals(
            [
                'EMAIL' => 'member2@example.com',
                'FNAME' => 'John',
                'LNAME' => 'Case'
            ],
            $members[1]->getMergeVarValues()
        );
    }

    public function testWriteNonFullNameAware(): void
    {
        $segment = $this->getReference('mailchimp:segment_two');

        $this->memberSyncWrite([$segment]);

        $memberRepository = $this->getContainer()->get('doctrine')->getRepository(Member::class);
        /** @var Member[] $members */
        $members = $memberRepository->findAll();
        self::assertCount(1, $members);

        self::assertEquals('customer1@example.com', $members[0]->getEmail());
        self::assertEmpty($members[0]->getFirstName());
        self::assertEmpty($members[0]->getLastName());
        self::assertEquals(['EMAIL' => 'customer1@example.com'], $members[0]->getMergeVarValues());
    }

    public function testWriteWithEmptyMergeVarsDataField(): void
    {
        $segment = $this->getReference('mailchimp:segment_three');

        $this->memberSyncWrite([$segment]);

        $memberRepository = $this->getContainer()->get('doctrine')->getRepository(Member::class);
        /** @var Member[] $members */
        $members = $memberRepository->findBy([], ['email' => 'ASC']);
        self::assertCount(2, $members);

        self::assertEquals('member1@example.com', $members[0]->getEmail());
        self::assertEmpty($members[0]->getMergeVarValues());

        self::assertEquals('member2@example.com', $members[1]->getEmail());
        self::assertEmpty($members[1]->getMergeVarValues());
    }

    private function memberSyncWrite(array $segments): void
    {
        $jobInstance = new JobInstance();
        $jobInstance->setRawConfiguration([
            'channel' => $this->getReference('mailchimp:channel_1')->getId(),
            'segments' => $segments
        ]);
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);
        $stepExecution = new StepExecution('import', $jobExecution);
        $context = new StepExecutionProxyContext($stepExecution);

        /** @var StaticSegmentReader $reader */
        $reader = $this->getContainer()->get('oro_mailchimp.importexport.reader.members_sync');
        $reader->initializeByContext($context);
        $reader->setStepExecution($stepExecution);
        $result = $reader->read();

        $writer = $this->getContainer()->get('oro_mailchimp.importexport.writer.members_sync');
        $writer->write([$result]);
    }
}
