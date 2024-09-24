<?php

namespace Oro\Bundle\MailChimpBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for mailchimp static segments.
 */
class StaticSegmentController extends RestController
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete MailChimp Static Segment List",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_mailchimp')]
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @ApiDoc(
     *      description="Update Static Segment status",
     *      resource=false
     * )
     * @param Request $request
     * @param StaticSegment $staticSegment
     * @return Response
     */
    #[ParamConverter('staticSegment', options: ['id' => 'id'])]
    #[QueryParam(name: 'id', requirements: '\d+', description: 'Static Segment Id', nullable: false)]
    #[AclAncestor('oro_mailchimp')]
    public function updateStatusAction(Request $request, StaticSegment $staticSegment)
    {
        $status = $request->get('status');
        $staticSegment->setSyncStatus($status);

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($staticSegment);
        $em->flush();

        return $this->handleView($this->view('', Response::HTTP_OK));
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_mailchimp.static_segment.manager.api');
    }

    #[\Override]
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    #[\Override]
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
