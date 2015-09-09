<?php

namespace Application\CatalogParser\Parser\Rozetka;

use Application\CatalogParser\Parser\BaseCatalogParser;

class RozetkaParser extends BaseCatalogParser
{
    public function parse()
    {
        while(
                !$this->isMaxItemsCountReached()
                &&
                ($page = $this->getCatalogPageIterator()->getNextPage())
        ){

            $pageResultContainer = $this->getParserChainFactory()->getChain("Page")->parse($page);
            $items = $pageResultContainer->get("items");

            if(!count($items)){
                break;
            }

            foreach($items as $item){
                $itemResultContainer = $this->getParserChainFactory()->getChain("Product")->parse($item);
                $this->addItem($itemResultContainer->toArray());

                if($this->isMaxItemsCountReached()){
                    break;
                }
            }

        }
    }

}
