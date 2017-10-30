<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\View as FOSView;
use Swagger\Annotations as SWG;
use App\RestApiComponent\Swagger\Annotations as AppSWG;

use AppBundle\Entity\Market;
use AppBundle\Form\MarketType;

/**
 * Markets API
 *
 * @RouteResource("Market")
 */
class MarketController extends AbstractMarketController
{
    /**
     * Get Markets
     *
     * @SWG\Get(
     *     path="/markets",
     *     summary="Get entities collection",
     *     tags={"Market"},
     *     @SWG\Parameter(
     *         ref="#/parameters/AppRestPagination"
     *     ),
     *     @SWG\Parameter(
     *         name="parent[eq]",
     *         in="query",
     *         description="Filter by parent GUID",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name[like]",
     *         in="query",
     *         description="Filter by description name",
     *         type="string"
     *     ),
     *     @AppSWG\ResponseSuccessGetResourceCollection(
     *         resource={
     *             @SWG\Schema(ref="#/definitions/AppMarket"),
     *             @SWG\Schema(ref="#/definitions/AppMarket_LocalesData")
     *         }
     *     )
     * )
     *
     * @param Request $request
     *
     * @FOSView(serializerGroups={"Default", "market-locales-data"})
     *
     * @return \FOS\RestBundle\View\View
     */
    public function cgetAction(Request $request)
    {
        $parameters = $this->get('app_rest.c_parameters_fetcher')
            ->configure()
                ->pagination()
                    ->allow()
                ->filter()
                    ->setAllowedFilters([
                        'name' => ['like'],
                        'keywords' => ['like'],
                        'parent' => ['eq']
                    ])
            ->fetch($request);

        $paginatorResult = $this->get('app.market')->findAll($parameters);

        return $this->createRestGetResouceCollectionView(
            $paginatorResult->getIterator()->getArrayCopy(),
            $parameters->getPagination(),
            $paginatorResult->count()
        );
    }

    /**
     * Get Market
     *
     * @SWG\Get(
     *     path="/markets/{guid}",
     *     summary="Get entity",
     *     tags={"Market"},
     *     @SWG\Parameter(
     *         name="guid",
     *         description="Entity guid",
     *         in="path",
     *         type="string",
     *         required=true
     *     ),
     *     @AppSWG\ResponseSuccessGetResource(
     *         resource={
     *             @SWG\Schema(ref="#/definitions/AppMarket"),
     *             @SWG\Schema(ref="#/definitions/AppMarket_LocalesData")
     *         }
     *     ),
     *     @AppSWG\ResponseErrorNotFound()
     * )
     *
     * @param $guid
     *
     * @FOSView(serializerGroups={"Default", "market-locales-data"})
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getAction($guid)
    {
        $market = $this->getByGuidOrThrow404($guid);

        return $this->createRestGetResourceView($market);
    }

    /**
     * Create Market
     *
     * @SWG\Post(
     *     path="/markets",
     *     summary="Create entity",
     *     tags={"Market"},
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_codeSegment"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_status"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_parent"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_nameEn"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_keywordsEn"
     *     ),
     *     @AppSWG\ResponseSuccessPostResource(
     *         resource={
     *             @SWG\Schema(ref="#/definitions/AppMarket"),
     *             @SWG\Schema(ref="#/definitions/AppMarket_LocalesData")
     *         }
     *     ),
     *     @AppSWG\ResponseErrorUnprocessableEntity()
     * )
     *
     * @param Request $request
     *
     * @FOSView(serializerGroups={"Default", "market-locales-data"})
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(MarketType::class, new Market(), ['http_method' => 'POST']);
        $form->submit($request->request->all());
        if (!$form->isValid()) {
            $this->throwRestUnprocessableFormException($form);
        }

        $market = $form->getData();

        $this->get('app.market')->saveWithDescriptionAndKeywords(
            $market,
            'en',
            $form->get('nameEn')->getData(),
            $form->get('keywordsEn')->getData()
        );

        return $this->createRestPostResourceView($market);
    }

    /**
     * Update Market
     *
     * @SWG\Patch(
     *     path="/markets/{guid}",
     *     summary="Update entity",
     *     tags={"Market"},
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarket_guid"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_codeSegment"
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/AppMarketType_status"
     *     ),
     *     @AppSWG\ResponseSuccessPatchResource(),
     *     @AppSWG\ResponseErrorNotFound(),
     *     @AppSWG\ResponseErrorUnprocessableEntity()
     * )
     *
     * @param Request $request
     * @param         $guid
     *
     * @return \FOS\RestBundle\View\View
     */
    public function patchAction(Request $request, $guid)
    {
        $market = $this->getByGuidOrThrow404($guid);

        $form = $this->createForm(MarketType::class, $market, ['http_method' => 'PATCH']);
        $form->submit($request->request->all(), false);

        if (!$form->isValid()) {
            $this->throwRestUnprocessableFormException($form);
        }

        $this->get('app.market')->save($market, true);

        return $this->createRestPatchResourceView();
    }

    /**
     * Delete Market
     *
     * @SWG\Delete(
     *     path="/markets/{guid}",
     *     summary="Remove entity",
     *     tags={"Market"},
     *     @SWG\Parameter(
     *         name="guid",
     *         description="Entity guid",
     *         in="path",
     *         type="string",
     *         required=true
     *     ),
     *     @AppSWG\ResponseSuccessDeleteResource(),
     *     @AppSWG\ResponseErrorNotFound()
     * )
     *
     * @param $guid
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deleteAction($guid)
    {
        $market = $this->getByGuidOrThrow404($guid);

        $this->get('app.market')->delete($market);

        return $this->createRestDeleteResourceView();
    }
}