<?php

namespace Application\CatalogParser\Parser\Rozetka;

use Application\CatalogParser\Parser\BaseCatalogPageIterator;

class RozetkaPageIterator extends BaseCatalogPageIterator
{
    protected
        $searchQuery,
        $currPage
    ;

    public function __construct($searchQuery)
    {
        $this->searchQuery = $searchQuery;
        $this->currPage = 0;
    }

    public function getNextPage()
    {
        if($this->currPage != 0){
            $this->delay();
        }
        
        $response = $this->getBrowser()->get($this->getSearchPageUrl());

        if($response->getStatusCode() != 200){
            return false;
        }

        $this->currPage ++;

        return $response->getContent();
    }

    protected function getSearchPageUrl()
    {
        return 'http://rozetka.com.ua/search/?' . http_build_query(array(
          'p' => $this->currPage,
          'text' => $this->searchQuery
        ));
    }
}
