<?php

namespace Oro\Bundle\MailChimpBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
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
     * @AclAncestor("oro_mailchimp")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @ParamConverter("staticSegment", options={"id"="id"})
     * @QueryParam(
     *      name="id",
     *      requirements="\d+",
     *      nullable=false,
     *      description="Static Segment Id"
     * )
     * @ApiDoc(
     *      description="Update Static Segment status",
     *      resource=false
     * )
     * @AclAncestor("oro_mailchimp")
     * @param Request $request
     * @param StaticSegment $staticSegment
     * @return Response
     */
    public function updateStatusAction(Request $request, StaticSegment $staticSegment)
    {
        $status = $request->get('status');
        $staticSegment->setSyncStatus($status);

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($staticSegment);
        $em->flush();

        return $this->handleView($this->view('', Response::HTTP_OK));
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_mailchimp.static_segment.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
