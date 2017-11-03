<?php
namespace App\RestApiComponent\Tests\Request\ParametersFetcher;

use Symfony\Component\HttpFoundation\Request;

use App\RestApiComponent\Request\ParametersFetcher\Configurator;
use App\RestApiComponent\Request\ResourceCollectionParametersFetcher;
use App\RestApiComponent\Request\Parameter\SearchDefinition;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    public function testChainCalls()
    {
        // GIVEN
        $search1Mock = $this->getMockBuilder(SearchDefinition::class)
            ->disableOriginalConstructor()
            ->setMethods(['allow', 'setDefaultValue'])
            ->getMock();
        $search1Mock->expects(self::AtLeastOnce ())->method('setDefaultValue')->with('Search1 default value');
        $search1Mock->expects(self::AtLeastOnce ())->method('allow');

        $search2Mock = $this->getMockBuilder(SearchDefinition::class)
            ->disableOriginalConstructor()
            ->setMethods(['deny', 'setDefaultValue'])
            ->getMock();
        $search2Mock->expects(self::AtLeastOnce ())->method('setDefaultValue')->with('Search2 default value');
        $search2Mock->expects(self::AtLeastOnce ())->method('deny');

        $fetcherMock = $this->getMockBuilder(ResourceCollectionParametersFetcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDefinition', 'fetch'])
            ->getMock();
        $fetcherMock
            ->expects(self::AtLeastOnce())
            ->method('getDefinition')
            ->will($this->returnValueMap([
                ['search1', $search1Mock],
                ['search2', $search2Mock],
            ]));
        $fetcherMock
            ->expects(self::once())
            ->method('fetch');

        $configurator = new Configurator($fetcherMock);

        // WHEN/THEN
        $configurator
            ->search1()                                     // select parameter to configure
                ->allow()                                   // configure parameter
                ->setDefaultValue('Search1 default value')  // configure parameter
            ->search2()                                     // select parameter to configure
                ->deny()                                    // configure parameter
                ->setDefaultValue('Search2 default value')  // configure parameter
            ->fetch(Request::create(''));                   // call fetcher method
    }
}