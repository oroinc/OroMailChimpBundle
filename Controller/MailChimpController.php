<?php

namespace Oro\Bundle\MailChimpBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Form\Handler\ConnectionFormHandler;
use Oro\Bundle\MailChimpBundle\Form\Type\MarketingListConnectionType;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClientFactory;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MailChimp Controller
 */
#[Route(path: '/mailchimp')]
class MailChimpController extends AbstractController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/ping', name: 'oro_mailchimp_ping')]
    #[AclAncestor('oro_mailchimp')]
    public function pingAction(Request $request)
    {
        try {
            $apiKey = $request->get('api_key');
            $mailChimpClientFactory = $this->container->get(MailChimpClientFactory::class);
            $client = $mailChimpClientFactory->create($apiKey);
            $ping = $client->ping();
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'result' => $ping,
            'msg' => array_key_exists('health_status', $ping) ? $ping['health_status'] : 'Connection successful',
        ]);
    }

    /**
     *
     * @param MarketingList $marketingList
     * @param Request $request
     * @return array
     */
    #[Route(
        path: '/manage-connection/marketing-list/{id}',
        name: 'oro_mailchimp_marketing_list_connect',
        requirements: ['id' => '\d+']
    )]
    #[Template]
    #[AclAncestor('oro_mailchimp')]
    public function manageConnectionAction(MarketingList $marketingList, Request $request)
    {
        $staticSegment = $this->getStaticSegmentByMarketingList($marketingList);
        $form = $this->createForm(MarketingListConnectionType::class, $staticSegment);
        $handler = new ConnectionFormHandler($request, $this->container->get('doctrine'), $form);

        $result = [];
        if ($savedSegment = $handler->process($staticSegment)) {
            $result['savedId'] = $savedSegment->getId();
            $staticSegment = $savedSegment;
        }

        $result['entity'] = $staticSegment;
        $result['form'] = $form->createView();

        return $result;
    }

    /**
     *
     *
     * @param MarketingList $marketingList
     * @return array
     */
    #[ParamConverter('marketingList', class: MarketingList::class, options: ['id' => 'entity'])]
    #[Template]
    #[AclAncestor('oro_mailchimp')]
    public function connectionButtonsAction(MarketingList $marketingList)
    {
        return [
            'marketingList' => $marketingList,
            'staticSegment' => $this->getStaticSegmentByMarketingList($marketingList),
        ];
    }

    /**
     *
     *
     * @param MarketingList $marketingList
     * @return array
     */
    #[Route(
        path: '/sync-status/{marketingList}',
        name: 'oro_mailchimp_sync_status',
        requirements: ['marketingList' => '\d+']
    )]
    #[ParamConverter('marketingList', class: MarketingList::class, options: ['id' => 'marketingList'])]
    #[Template]
    #[AclAncestor('oro_mailchimp')]
    public function marketingListSyncStatusAction(MarketingList $marketingList)
    {
        return ['static_segment' => $this->findStaticSegmentByMarketingList($marketingList)];
    }

    /**
     *
     *
     * @param EmailCampaign $emailCampaign
     * @return array
     */
    #[Route(
        path: '/email-campaign-status-positive/{entity}',
        name: 'oro_mailchimp_email_campaign_status',
        requirements: ['entity' => '\d+']
    )]
    #[ParamConverter('emailCampaign', class: EmailCampaign::class, options: ['id' => 'entity'])]
    #[Template]
    #[AclAncestor('oro_mailchimp')]
    public function emailCampaignStatsAction(EmailCampaign $emailCampaign)
    {
        $campaign = $this->getCampaignByEmailCampaign($emailCampaign);

        return ['campaignStats' => $campaign];
    }

    /**
     *
     *
     * @param EmailCampaign $emailCampaign
     * @return array
     */
    #[ParamConverter('emailCampaign', class: EmailCampaign::class, options: ['id' => 'entity'])]
    #[Template]
    #[AclAncestor('oro_mailchimp')]
    public function emailCampaignActivityUpdateButtonsAction(EmailCampaign $emailCampaign)
    {
        return [
            'emailCampaign' => $emailCampaign,
            'campaign' => $this->getCampaignByEmailCampaign($emailCampaign)
        ];
    }

    /**
     *
     * @param EmailCampaign $emailCampaign
     * @return JsonResponse
     */
    #[Route(
        path: '/email-campaign/{id}/activity-updates/toggle',
        name: 'oro_mailchimp_email_campaign_activity_update_toggle',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_mailchimp')]
    #[CsrfProtection()]
    public function toggleUpdateStateAction(EmailCampaign $emailCampaign)
    {
        /** @var MailChimpTransportSettings $settings */
        $settings = $emailCampaign->getTransportSettings();
        $settings->setReceiveActivities(!$settings->isReceiveActivities());

        $em = $this->container->get('doctrine')->getManagerForClass(ClassUtils::getClass($settings));
        $em->persist($settings);
        $em->flush();

        if ($settings->isReceiveActivities()) {
            $message = 'oro.mailchimp.controller.email_campaign.receive_activities.enabled.message';
        } else {
            $message = 'oro.mailchimp.controller.email_campaign.receive_activities.disabled.message';
        }

        return new JsonResponse(['message' => $this->container->get(TranslatorInterface::class)->trans($message)]);
    }

    /**
     * @param MarketingList $marketingList
     * @return StaticSegment
     */
    protected function getStaticSegmentByMarketingList(MarketingList $marketingList)
    {
        $staticSegment = $this->findStaticSegmentByMarketingList($marketingList);

        if (!$staticSegment) {
            $staticSegment = new StaticSegment();
            $staticSegment->setName(mb_substr($marketingList->getName(), 0, 100));
            $staticSegment->setSyncStatus(StaticSegment::STATUS_NOT_SYNCED);
            $staticSegment->setMarketingList($marketingList);
        }

        return $staticSegment;
    }

    /**
     * @param MarketingList $marketingList
     * @return StaticSegment
     */
    protected function findStaticSegmentByMarketingList(MarketingList $marketingList)
    {
        return $this->container->get('doctrine')
            ->getRepository(StaticSegment::class)
            ->findOneBy(['marketingList' => $marketingList]);
    }

    /**
     * @param EmailCampaign $emailCampaign
     * @return Campaign
     */
    protected function getCampaignByEmailCampaign(EmailCampaign $emailCampaign)
    {
        $campaign = $this->container->get('doctrine')
            ->getRepository(Campaign::class)
            ->findOneBy(['emailCampaign' => $emailCampaign]);

        return $campaign;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            MailChimpClientFactory::class,
            TranslatorInterface::class,
            'doctrine' => ManagerRegistry::class,
        ]);
    }
}
