<?php

namespace Application\ParserChain\ParserUnit;

use Application\ParserChain\Container\ResultContainer;

interface ParserUnitInterface
{
    public function parse($text, ResultContainer $resultContainer);
}