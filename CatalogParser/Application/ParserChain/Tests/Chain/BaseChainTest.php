<?php

namespace Application\ParserChain\Tests\Chain;

class BaseChainTest extends \PHPUnit_Framework_TestCase
{
    public function testParse_ShouldCallDoParse()
    {
        // given
        $mockChain = $this->getMockForAbstractClass(
            '\Application\ParserChain\Chain\BaseChain'
        );
        $mockChain->expects($this->once())
            ->method('doParse')
            ->with('some');

        // when
        $mockChain->parse('some');
    }

    public function testParse_ShouldClearResultContainerFromPreviousResults()
    {
        // given
        $mockChain = $this->getMockForAbstractClass(
            '\Application\ParserChain\Chain\BaseChain'
        );
        $mockChain->getResultContainer()->add('some');

        // when
        $mockChain->parse('some');

        // then
        $this->assertEmpty($mockChain->getResultContainer()->toArray(), 'Result container should be empty');
    }

}