<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\ImportExport\Reader\StaticSegmentReader;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadStaticSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MemberSyncWriterTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([LoadStaticSegmentData::class]);
    }

    public function testWrite()
    {
        $channel = $this->getReference('mailchimp:channel_1')->getId();
        $segment = $this->getReference('mailchimp:segment_one');

        $jobInstance = new JobInstance();
        $jobInstance->setRawConfiguration([
            'channel' => $channel,
            'segments' => [$segment]
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

        $repo = $this->getContainer()->get('doctrine')->getRepository(Member::class);
        /** @var Member[] $members */
        $members = $repo->findBy([], ['email' => 'ASC']);
        $this->assertCount(2, $members);

        $this->assertEquals('member1@example.com', $members[0]->getEmail());
        $this->assertEquals("Daniel\tst", $members[0]->getFirstName());
        $this->assertEquals(
            'Case <a href="https://www.goo.com/search?q=json&oq=json&aqs=chrome..69">Дж.[s`ón]</a>',
            $members[0]->getLastName()
        );
        $this->assertEquals(
            [
                'EMAIL' => 'member1@example.com',
                'FNAME' => "Daniel\tst",
                'LNAME' => 'Case <a href="https://www.goo.com/search?q=json&oq=json&aqs=chrome..69">Дж.[s`ón]</a>'
            ],
            $members[0]->getMergeVarValues()
        );

        $this->assertEquals('member2@example.com', $members[1]->getEmail());
        $this->assertEquals('John', $members[1]->getFirstName());
        $this->assertEquals('Case', $members[1]->getLastName());
        $this->assertEquals(
            [
                'EMAIL' => 'member2@example.com',
                'FNAME' => 'John',
                'LNAME' => 'Case'
            ],
            $members[1]->getMergeVarValues()
        );
    }
}
