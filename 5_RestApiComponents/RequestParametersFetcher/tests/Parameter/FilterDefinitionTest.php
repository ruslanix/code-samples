<?php
namespace App\RestApiComponent\Tests\Request\Parameter;

use Symfony\Component\HttpFoundation\Request;

use App\RestApiComponent\Request\Parameter;
use App\RestApiComponent\Request\Parameter\FilterDefinition;
use App\RestApiComponent\Request\Parameter\Filter;

class FilterDefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderTestParseRequest()
    {
        $data = [];

        // `NULL` CONDITION CASE
        $data[] = [
            // request
            Request::create(
                "?state[null]"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['state']),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_IS_NULL, null)
            ]
        ];

        // `NOTNULL` CONDITION CASE
        $data[] = [
            // request
            Request::create(
                "?state[notnull]"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['state']),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_IS_NOT_NULL, null)
            ]
        ];

        // `EQ` CONDITION CASE
        $data[] = [
            // request
            Request::create(
                "?state[eq]=active"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['state']),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_EQ, 'active')
            ]
        ];

        // `LIKE` CONDITION CASE
        $data[] = [
            // request
            Request::create(
                "?state[like]=active"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['state']),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_LIKE, 'active')
            ]
        ];

        // DEFAULT CONDITION SHOULD BE `EQ`
        $data[] = [
            // request
            Request::create(
                "?state=active"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['state']),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_EQ, 'active')
            ]
        ];

        // UPPERCASE CONDITIONS
        $data[] = [
            // request
            Request::create(
                "?p1[LIKE]=v1&p2[EQ]=v2&p3[NULL]&p4[NOTNULL]"
            ),
            // filter definition
            (new FilterDefinition())->setAllowedFilters(['p1', 'p2', 'p3', 'p4']),
            // expected filters
            [
                new Filter('p1', Filter::CONDITION_LIKE, 'v1'),
                new Filter('p2', Filter::CONDITION_EQ, 'v2'),
                new Filter('p3', Filter::CONDITION_IS_NULL, null),
                new Filter('p4', Filter::CONDITION_IS_NOT_NULL, null)
            ]
        ];

        // CASE - PARAMETER IS DENIED AND NOT EXISTS IN REQUEST AND DEFAULT VALUE IS SET - NULL SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                ""
            ),
            // definition
            (new Parameter\FilterDefinition())
                ->deny()
                ->setDefaultValue(
                    new Parameter\Filter('a', Parameter\Filter::CONDITION_EQ, 'b')
                ),
            // expected result
            null
        ];

        // CASE - PARAMETER IS ALLOWED AND EXISTS IN REQUEST AND DEFAULT VALUE IS SET
        // - VALUE FROM REQUEST SHOULD BE RETURNED
        $data[] = [
            // request
            Request::create(
                "?state[like]=active"
            ),
            // filter definition
            (new FilterDefinition())
                ->setAllowedFilters(['state'])
                ->setDefaultValue(new Parameter\Filter('state', Parameter\Filter::CONDITION_EQ, 'deactivated')),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_LIKE, 'active')
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