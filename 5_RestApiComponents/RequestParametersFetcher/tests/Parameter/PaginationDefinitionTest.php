<?php
namespace App\RestApiComponent\Tests\Request\Parameter;

use Symfony\Component\HttpFoundation\Request;

use App\RestApiComponent\Request\Parameter;

class PaginationDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderTestParseRequest()
    {
        $data = [];

        // CASE - PARAMETER IS DENIED AND EXISTS IN REQUEST - EXCEPTION SHOULD BE THROUWN
        $data[] = [
            // request
            Request::create(
                "?range=10-20"
            ),
            // definition
            (new Parameter\PaginationDefinition())->deny(),
            // expected result
            null,
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'range\' denied.'
        ];

        // CASE - PARAMETER IS DENIED AND NOT EXISTS IN REQUEST - NULL SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                ""
            ),
            // definition
            (new Parameter\PaginationDefinition())->deny(),
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
                ""
            ),
            // definition
            (new Parameter\PaginationDefinition())
                ->deny()
                ->setDefaultValue(
                    new Parameter\Pagination(0, 10)
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
                ""
            ),
            // definition
            (new Parameter\PaginationDefinition())
                ->allow()
                ->setDefaultValue(
                    $defaultValue = new Parameter\Pagination(0, 10)
                ),
            // expected result
            $defaultValue,
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
                "?range=10-20"
            ),
            // definition
            (new Parameter\PaginationDefinition())
                ->allow()
                ->setDefaultValue(
                    new Parameter\Pagination(0, 10)
                ),
            // expected result
            (new Parameter\Pagination(10, 11))
                ->setIsRequestedRange(true)
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