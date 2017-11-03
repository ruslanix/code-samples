<?php

namespace App\RestApiComponent\Request;

use App\RestApiComponent\ResourceCollection\ParametersInterface as CollectionParametersInterface;

/**
 * Holds standards parameters for resource collection request.
 * Add additional methods to base class 'ParameterBag' for quick access
 */
class ResourceCollectionParameterBag extends ParameterBag implements CollectionParametersInterface
{
    /**
     *
     * @return string|null
     */
    public function getSearch()
    {
        $search = $this->get('search');

        return $search instanceof Parameter\Search ? $search->getValue() : null;
    }

    /**
     *
     * @return \App\RestApiComponent\Request\Parameter\Filter[]
     */
    public function getFilters()
    {
        $filters = $this->get('filter');
        
        if (!is_array($filters)) {
            return [];
        }

        foreach ($filters as $index => $filter) {
            if (!$filter instanceof Parameter\Filter) {
                unset($filters[$index]);
            }
        }

        return $filters;
    }

    /**
     *
     * @param string $field
     * @return \App\RestApiComponent\Request\Parameter\Filter | null
     */
    public function getFilter($field)
    {
        foreach ($this->getFilters() as $filter) {
            if ($filter->getField() === $field) {
                return $filter;
            }
        }

        return null;
    }

    /**
     *
     * @return \App\RestApiComponent\Request\Parameter\Pagination|null
     */
    public function getPagination()
    {
        $pagination = $this->get('pagination');

        return $pagination instanceof Parameter\Pagination ? $pagination : null;
    }

    /**
     *
     * @return \App\RestApiComponent\Request\Parameter\Sort[]
     */
    public function getSorting()
    {
        $sorting = $this->get('sort');

        if (!is_array($sorting)) {
            return [];
        }

        foreach ($sorting as $index => $sort) {
            if (!$sort instanceof Parameter\Sort) {
                unset($sorting[$index]);
            }
        }

        return $sorting;
    }

    /**
     *
     * @param string $field
     * @return \App\RestApiComponent\Request\Parameter\Sort | null
     */
    public function getSort($field)
    {
        foreach ($this->getSorting() as $sort) {
            if ($sort->getField() === $field) {
                return $sort;
            }
        }

        return null;
    }
}
