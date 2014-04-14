<?php

namespace Application\ParserChain\Tests\Chain;

use Application\ParserChain\Chain\SimpleChain;

class SimpleChainTest extends \PHPUnit_Framework_TestCase
{
    public function testParse_ShouldCallToEachParserUnit()
    {
        // given
        $chain = new SimpleChain();

        for($i = 0; $i < 3; $i++){
            $mockParserUnit = $this->getMockForAbstractClass(
                '\Application\ParserChain\ParserUnit\BaseParserUnit'
            );
            $mockParserUnit->expects($this->once())
                ->method('parse')
                ->with('some', $chain->getResultContainer());
            
            $chain->addParserUnit($mockParserUnit);
        }

        // when
        $chain->parse('some');
    }
}