<?php

namespace Application\CatalogParser\Parser;

interface CatalogPageIteratorInterface
{
    public function setDelay($delay);
    public function getNextPage();
}
