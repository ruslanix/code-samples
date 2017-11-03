<?php

namespace App\RestApiComponent\Request;

use App\RestApiComponent\Request\Parameter;

/**
 * Fetch standards parameters for resource collection request.
 * Add additional methods to base class 'AbstractParametersFetcher' for quick access
 *
 * @method ResourceCollectionParameterBag fetch($request) Fetch and parse Request parameters
 */
class ResourceCollectionParametersFetcher extends AbstractParametersFetcher
{
    public function __construct()
    {
        $this->addDefinition('filter', new Parameter\FilterDefinition());
        $this->addDefinition('pagination', new Parameter\PaginationDefinition());
        $this->addDefinition('search', new Parameter\SearchDefinition());
        $this->addDefinition('sort', new Parameter\SortDefinition());
    }

    /**
     *
     * @return ResourceCollectionParameterBag
     */
    protected function createParameterBag()
    {
        return new ResourceCollectionParameterBag();
    }
}
