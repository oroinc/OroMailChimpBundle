<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\BatchJob;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;
use Oro\Bundle\MailChimpBundle\Provider\Connector\MemberActivityConnector;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadCampaignData;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadMemberData;
use Oro\Bundle\MailChimpBundle\Tests\Functional\Stub\MailChimpClientStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

class MemberActivityImportTest extends WebTestCase
{
    private JobExecutor $jobExecutor;

    #[\Override]
    protected function setUp(): void
    {
        $this->markTestSkipped('CRM-8206');

        $this->initClient();

        $this->jobExecutor = $this->getContainer()->get('oro_importexport.job_executor');

        $this->getContainer()
            ->get('oro_mailchimp.client.factory')
            ->setClientClass(MailChimpClientStub::class);

        $this->loadFixtures([LoadCampaignData::class, LoadMemberData::class]);
    }

    public function testRunJob()
    {
        $jobResult = $this->jobExecutor->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            MemberActivityConnector::JOB_IMPORT,
            [
                ProcessorRegistry::TYPE_IMPORT => [
                    'channel' => $this->getReference('mailchimp:channel_1')->getId(),
                    'channelType' => $this->getReference('mailchimp:channel_1')->getType(),
                    'processorAlias' => 'test'
                ]
            ]
        );

        $this->assertTrue($jobResult->isSuccessful(), implode(',', $jobResult->getFailureExceptions()));
        $this->assertEquals(
            0,
            $jobResult->getContext()->getErrorEntriesCount(),
            implode(', ', $jobResult->getContext()->getErrors())
        );
        $this->assertDatabaseContent($jobResult);
    }

    private function assertDatabaseContent(JobResult $jobResult): void
    {
        $fixtures = new \RecursiveDirectoryIterator(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . 'fixtures',
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $campaignRepo = $this->getContainer()->get('doctrine')->getRepository(Campaign::class);
        $repository = $this->getContainer()->get('doctrine')->getRepository(MemberActivity::class);

        $addCount = 0;
        $fullCount = 0;
        foreach ($fixtures as $file) {
            $data = Yaml::parse(file_get_contents($file->getPathName()));
            $addCount += $data['addCount'];
            $fullCount += $data['fullCount'];

            foreach ($data['database'] as $criteria) {
                $campaign = $campaignRepo->findOneBy(['originId' => $criteria['campaign']]);
                $criteria['campaign'] = $campaign->getId();
                if (!empty($criteria['activityTime'])) {
                    $criteria['activityTime'] = new \DateTime($criteria['activityTime'], new \DateTimeZone('UTC'));
                }

                $result = $repository->findBy($criteria);

                $this->assertCount(1, $result, $file->getFileName());
            }
        }

        $this->assertEquals($addCount, $jobResult->getContext()->getAddCount());
        $this->assertCount($fullCount, $repository->findAll());
    }
}
