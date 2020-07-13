<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Controller\Api_nelmio_3;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View as FOSRestView;
use FOS\UserBundle\Model\GroupInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Sonata\UserBundle\Model\GroupManagerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class GroupController
{
    /**
     * @var GroupManagerInterface
     */
    protected $groupManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param GroupManagerInterface $groupManager Sonata group manager
     * @param FormFactoryInterface  $formFactory  Symfony form factory
     */
    public function __construct(GroupManagerInterface $groupManager, FormFactoryInterface $formFactory)
    {
        $this->groupManager = $groupManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Returns a paginated list of groups.
     *
     * @Operation(
     *     operationId="getGroups",
     *     summary="Returns a paginated list of groups.",
     *     @SWG\Response(
     *         description="Returned when successful",
     *         response="200",
     *         @SWG\Schema(ref=@Model(type=Sonata\DatagridBundle\Pager\PagerInterface::class, groups={"sonata_api_read"}))
     *     )
     * )
     *
     * @Get("/groups")
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page for groups list pagination (1-indexed)")
     * @QueryParam(name="count", requirements="\d+", default="10", description="Number of groups by page")
     * @QueryParam(name="orderBy", map=true, requirements="ASC|DESC", nullable=true, strict=true, description="Query groups order by clause (key is field, value is direction")
     * @QueryParam(name="enabled", requirements="0|1", nullable=true, strict=true, description="Enabled/disabled groups only?")
     *
     * @View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @return PagerInterface
     */
    public function getGroupsAction(ParamFetcherInterface $paramFetcher)
    {
        $supportedFilters = [
            'enabled' => '',
        ];

        $page = $paramFetcher->get('page');
        $limit = $paramFetcher->get('count');
        $sort = $paramFetcher->get('orderBy');
        $criteria = array_intersect_key($paramFetcher->all(), $supportedFilters);

        foreach ($criteria as $key => $value) {
            if (null === $value) {
                unset($criteria[$key]);
            }
        }

        if (!$sort) {
            $sort = [];
        } elseif (!\is_array($sort)) {
            $sort = [$sort, 'asc'];
        }

        return $this->groupManager->getPager($criteria, $page, $limit, $sort);
    }

    /**
     * Retrieves a specific group.
     *
     * @Operation(
     *     operationId="getGroup",
     *     summary="Retrieves a specific group.",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="group id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type=Sonata\UserBundle\Model\GroupInterface::class, groups={"sonata_api_read"}))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when group is not found"
     *     )
     * )
     *
     * @Get("/group/{id}")
     *
     * @View(serializerGroups={"sonata_api_read"}, serializerEnableMaxDepthChecks=true)
     *
     * @param $id
     *
     * @return GroupInterface
     */
    public function getGroupAction($id)
    {
        return $this->getGroup($id);
    }

    /**
     * Adds a group.
     *
     * @Operation(
     *     operationId="postGroup",
     *     summary="Adds a group.",
     *     @SWG\Parameter(
     *         name="",
     *         in="body",
     *         required=true,
     *         @Model(type=Sonata\UserBundle\Form\Type\ApiGroupType::class, groups={"sonata_api_write"})
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type=Sonata\UserBundle\Model\Group::class, groups={"sonata_api_read"}))
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while group creation"
     *     )
     * )
     *
     * @Post("/group")
     *
     * @param Request $request A Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GroupInterface
     */
    public function postGroupAction(Request $request)
    {
        return $this->handleWriteGroup($request);
    }

    /**
     * Updates a group.
     *
     * @Operation(
     *     operationId="putGroup",
     *     summary="Updates an group.",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="group identifier",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="",
     *         in="body",
     *         required=true,
     *         @Model(type=Sonata\UserBundle\Form\Type\ApiGroupType::class, groups={"sonata_api_write"})
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful",
     *         @SWG\Schema(ref=@Model(type=Sonata\UserBundle\Model\Group::class, groups={"sonata_api_read"}))
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while group creation"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when unable to find group"
     *     )
     * )
     *
     * @Put("/group/{id}")
     *
     * @param int     $id      Group identifier
     * @param Request $request A Symfony request
     *
     * @throws NotFoundHttpException
     *
     * @return GroupInterface
     */
    public function putGroupAction($id, Request $request)
    {
        return $this->handleWriteGroup($request, $id);
    }

    /**
     * Deletes a group.
     *
     * @Operation(
     *     operationId="deleteGroup",
     *     summary="Deletes a group.",
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="group identifier",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when group is successfully deleted"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Returned when an error has occurred while group deletion"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Returned when unable to find group"
     *     )
     * )
     *
     * @Delete("/group/{id}")
     *
     * @param int $id A Group identifier
     *
     * @throws NotFoundHttpException
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deleteGroupAction($id)
    {
        $group = $this->getGroup($id);

        $this->groupManager->deleteGroup($group);

        return ['deleted' => true];
    }

    /**
     * Write a Group, this method is used by both POST and PUT action methods.
     *
     * @param Request  $request Symfony request
     * @param int|null $id      A Group identifier
     *
     * @return FormInterface
     */
    protected function handleWriteGroup($request, $id = null)
    {
        $groupClassName = $this->groupManager->getClass();
        $group = $id ? $this->getGroup($id) : new $groupClassName('');

        $form = $this->formFactory->createNamed(null, 'sonata_user_api_form_group', $group, [
            'csrf_protection' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $group = $form->getData();
            $this->groupManager->updateGroup($group);

            $context = new Context();
            $context->setGroups(['sonata_api_read']);
            $context->enableMaxDepth();

            $view = FOSRestView::create($group);
            $view->setContext($context);

            return $view;
        }

        return $form;
    }

    /**
     * Retrieves group with id $id or throws an exception if it doesn't exist.
     *
     * @param $id
     *
     * @throws NotFoundHttpException
     *
     * @return GroupInterface
     */
    protected function getGroup($id)
    {
        $group = $this->groupManager->findGroupBy(['id' => $id]);

        if (null === $group) {
            throw new NotFoundHttpException(sprintf('Group (%d) not found', $id));
        }

        return $group;
    }
}
