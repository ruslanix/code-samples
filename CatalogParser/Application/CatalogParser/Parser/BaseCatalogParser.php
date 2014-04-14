<?php

namespace Application\CatalogParser\Parser;

abstract class BaseCatalogParser implements CatalogParserInterface
{
    protected
        $maxItemsCount,
        $items,
        $pageIterator,
        $chainFactory
    ;

    public function __construct()
    {
        $this->items = array();
        $this->maxItemsCount = 100;
    }

    public function setCatalogPageIterator(CatalogPageIteratorInterface $pageIterator)
    {
        $this->pageIterator = $pageIterator;
    }

    public function getCatalogPageIterator()
    {
        return $this->pageIterator;
    }

    public function setParserChainFactory(ParserChainFactoryInterface $chainFactory)
    {
        $this->chainFactory = $chainFactory;
    }

    public function getParserChainFactory()
    {
        return $this->chainFactory;
    }

    public function setMaxItemsCount($cnt)
    {
        $this->maxItemsCount = $cnt;
    }

    public function getItems()
    {
        return $this->items;
    }

    protected function addItem($item)
    {
        $this->items[] = $item;
    }

    protected function isMaxItemsCountReached()
    {
        return count($this->getItems()) >= $this->maxItemsCount;
    }
}
