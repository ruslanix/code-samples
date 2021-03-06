<?php
namespace App\RestApiComponent\Tests\Request\Parameter;

use Symfony\Component\HttpFoundation\Request;

use App\RestApiComponent\Request\Parameter;

class SortDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderTestParseRequest()
    {
        $data = [];

        // CASE - PARAMETER IS DENIED AND EXISTS IN REQUEST - EXCEPTION SHOULD BE THROUWN
        $data[] = [
            // request
            Request::create(
                "?sort=a"
            ),
            // definition
            (new Parameter\SortDefinition())->deny(),
            // expected result
            null,
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'sort\' denied.'
        ];

        // CASE - PARAMETER IS DENIED AND NOT EXISTS IN REQUEST - NULL SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                "?a=b"
            ),
            // definition
            (new Parameter\SortDefinition())->deny(),
            // expected result
            null,
            // expected Exception
            null,
            // expected Exception message
            null
        ];

        // CASE - PARAMETER IS DENIED AND NOT EXISTS IN REQUEST AND DEFAULT VALUE IS SET - NULL SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                "?a=b"
            ),
            // definition
            (new Parameter\SortDefinition())
                ->deny()
                ->setDefaultValue(
                    new Parameter\Sort('a', Parameter\Sort::ORDER_ASC)
                ),
            // expected result
            null,
            // expected Exception
            null,
            // expected Exception message
            null
        ];

        // CASE - PARAMETER IS ALLOWED AND NOT EXISTS IN REQUEST AND DEFAULT VALUE IS SET
        // - DEFAULT VALUE SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                "?a=b"
            ),
            // definition
            (new Parameter\SortDefinition())
                ->allow()
                ->setDefaultValue(
                    $defaultValue = new Parameter\Sort('a', Parameter\Sort::ORDER_ASC)
                ),
            // expected result
            [$defaultValue],
            // expected Exception
            null,
            // expected Exception message
            null
        ];

        // CASE - PARAMETER IS ALLOWED AND EXISTS IN REQUEST AND DEFAULT VALUE IS SET
        // - VALUE FROM REQUEST SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                "?sort=a,b;desc=b"
            ),
            // definition
            (new Parameter\SortDefinition())
                ->allow()
                ->setDefaultValue(
                    new Parameter\Sort('a', Parameter\Sort::ORDER_ASC)
                ),
            // expected result
            [
                new Parameter\Sort('a', Parameter\Sort::ORDER_ASC),
                new Parameter\Sort('b', Parameter\Sort::ORDER_DESC)
            ]
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestParseRequest
     *
     * @param Request $request
     * @param \App\RestApiComponent\Request\Parameter\AbstractParameterDefinition $definition
     * @param mixed|null $expectedResult
     * @param Exception|null $expectedException
     * @param string|null $expectedExceptionMessage
     */
    public function testParseRequest(
        Request $request,
        Parameter\AbstractParameterDefinition $definition,
        $expectedResult,
        $expectedException = null,
        $expectedExceptionMessage = null)
    {
        // GIVEN
        if ($expectedException) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }
        
        // WHEN
        $result = $definition->parseRequest($request);
        
        // THEN
        $this->assertEquals($expectedResult, $result, "Wrong result");
    }
}