<?php
namespace App\RestApiComponent\Tests\Request;

use Symfony\Component\HttpFoundation\Request;

use App\RestApiComponent\Request\ResourceCollectionParametersFetcher;
use App\RestApiComponent\Request\Parameter\Filter;
use App\RestApiComponent\Request\Parameter\Search;
use App\RestApiComponent\Request\Parameter\Sort;
use App\RestApiComponent\Request\Parameter\Pagination;

class ResourceCollectionParametersFetcherTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderTestFetch_ParamsSetAndValid()
    {
        $data = [];

        $fetcher = new ResourceCollectionParametersFetcher();
        $fetcher
            ->configure()
                ->filter()
                    ->setAllowedFilters([
                        'state',
                        'name' => [Filter::CONDITION_LIKE]
                    ])
                ->pagination()
                    ->allow()
                ->search()
                    ->allow()
                ->sort()
                    ->allow();

        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?q=active+users&sort=name,date,status;desc=date&range=10-20&state=1&name[like]=Max"
            ),
            // expected pagination
            (new Pagination(10, 11))->setIsRequestedRange(true),
            // expected search
            new Search('active users'),
            // expected filters
            [
                new Filter('state', Filter::CONDITION_EQ, 1),
                new Filter('name', Filter::CONDITION_LIKE, 'Max')
            ],
            // expected sorting
            [
                new Sort('name', Sort::ORDER_ASC),
                new Sort('date', Sort::ORDER_DESC),
                new Sort('status', Sort::ORDER_ASC)
            ]
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_ParamsSetAndValid
     */
    public function testFetch_ParamsSetAndValid(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        Pagination $expectedPagination = null,
        Search $expectedSearch = null,
        array $expectedFilters = [],
        array $expectedSorting = []
    )
    {
        $this->assertFetchSuccessful(
            $fetcher,
            $request,
            $expectedPagination,
            $expectedSearch,
            $expectedFilters,
            $expectedSorting
        );

        $this->assertTrue($expectedPagination->isRequestedRange());
    }

    public function dataProviderTestFetch_DefaultValuesSet()
    {
        $data = [];

        // CASE 1 - PARAMETERS DON'T EXIST IN QUERY

        $fetcher1 = new ResourceCollectionParametersFetcher();
        $fetcher1
            ->configure()
                ->filter()
                    ->allow()
                    ->setDefaultValue(
                        $defaultFilter = new Filter('state', Filter::CONDITION_EQ, 1)
                    )
                ->pagination()
                    ->allow()
                    ->setDefaultValue(
                            $defaultPagination = new Pagination(0, 25)
                    )
                ->search()
                    ->allow()
                    ->setDefaultValue(
                        $defaultSearch = new Search('default value')
                    )
                ->sort()
                    ->allow()
                    ->setDefaultValue(
                        $defaultSorting = [new Sort('status', Sort::ORDER_ASC), new Sort('name', Sort::ORDER_DESC)]
                    );

        $data[] = [
            // fetcher
            $fetcher1,
            // request
            Request::create(
                ""
            ),
            // expected pagination
            $defaultPagination,
            // expected search
            $defaultSearch,
            // expected filters
            [
                $defaultFilter
            ],
            // expected sorting
            $defaultSorting
        ];

        // CASE 2 - SEARCH PARAMETER EXISTS BUT HAVE EMPTY VALUE (EMPTY VALUE SHOULD BE RETURNED)

        $fetcher2 = new ResourceCollectionParametersFetcher();
        $fetcher2
            ->configure()
                ->search()
                    ->allow()
                    ->setDefaultValue($defaultSearch);

        $data[] = [
            // fetcher
            $fetcher2,
            // request
            Request::create(
                "?q"
            ),
            // expected pagination
            null,
            // expected search
            new Search(''),
            // expected filters
            [],
            // expected sorting
            []
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_DefaultValuesSet
     */
    public function testFetch_DefaultValuesSet(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        Pagination $expectedPagination = null,
        Search $expectedSearch = null,
        array $expectedFilters = [],
        array $expectedSorting = []
    )
    {
        $this->assertFetchSuccessful(
            $fetcher,
            $request,
            $expectedPagination,
            $expectedSearch,
            $expectedFilters,
            $expectedSorting
        );

        if ($expectedPagination) {
            $this->assertFalse($expectedPagination->isRequestedRange());
        }
    }

    public function dataProviderTestFetch_FilterParameterInvalid()
    {
        $data = [];

        // CASE - FIELD IS NOT CONFIGURED AS FITLER
        $fetcher1 = new ResourceCollectionParametersFetcher();

        $data[] = [
            // fetcher
            $fetcher1,
            // request
            Request::create(
                "?active=1"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'active\' considered as filter but it is not allowed to filter by this field'
        ];

        // CASE - FILTER CONDITION IS NOT ALLOWED
        $fetcher2 = new ResourceCollectionParametersFetcher();
        $fetcher2
            ->configure()
                ->filter()
                    ->setAllowedFilters([
                        'active' => [Filter::CONDITION_LIKE]
                    ]);

        $data[] = [
            // fetcher
            $fetcher2,
            // request
            Request::create(
                "?active=1"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'active\' considered as filter but has not allowed filter condition \'eq\''
        ];

        // CASE - FILTER VALUE IS EMPTY
        $fetcher3 = new ResourceCollectionParametersFetcher();
        $fetcher3
            ->configure()
                ->filter()
                    ->setAllowedFilters([
                        'active'
                    ]);

        $data[] = [
            // fetcher
            $fetcher3,
            // request
            Request::create(
                "?active"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'active\' considered as filter but has empty value'
        ];

        $data[] = [
            // fetcher
            $fetcher3,
            // request
            Request::create(
                "?active[like]"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'active\' considered as filter but has empty value'
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_FilterParameterInvalid
     */
    public function testFetch_FilterParameterInvalid(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        $expectedException,
        $expectedExceptionMessage
    )
    {
        $this->assertFetchFail(
            $fetcher,
            $request,
            $expectedException,
            $expectedExceptionMessage
        );
    }

    public function dataProviderTestFetch_SortParameterInvalid()
    {
        $data = [];

        // CASE - EMPTY VALUE
        $fetcher = new ResourceCollectionParametersFetcher();
        $fetcher
            ->configure()
                ->sort()
                    ->allow();

        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?sort"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'sort\' value has malformed format. ' .
            'Example of valid value: \'range=name,status;desc=name\''
        ];

        // CASE - WRONG FORMAT
        $wrongFormats = [
            "?sort=name,",
            "?sort=name,date;",
            "?sort=name;desc",
            "?sort=name;desc=",
            "?sort=name;desc=name,"
        ];
        foreach ($wrongFormats as $format) {
            $data[] = [
                // fetcher
                $fetcher,
                // request
                Request::create(
                    $format
                ),
                // expected Exception
                'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
                // expected Exception message
                'Request parameter \'sort\' value has malformed format. ' .
                'Example of valid value: \'range=name,status;desc=name\''
            ];
        }

        // CASE - DESC PART REFERENCE TO FIELDS NOT PRESENTED IN SORT PART
        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?sort=name,date;desc=name,lastname"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'sort\' has malformed value - desc part contains fields \'lastname\''
            . ' that are not listed in asc part'
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_SortParameterInvalid
     */
    public function testFetch_SortParameterInvalid(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        $expectedException,
        $expectedExceptionMessage
    )
    {
        $this->assertFetchFail(
            $fetcher,
            $request,
            $expectedException,
            $expectedExceptionMessage
        );
    }

    public function dataProviderTestFetch_SortParameterIsValid()
    {
        $data = [];

        $fetcher = new ResourceCollectionParametersFetcher();
        $fetcher
            ->configure()
                ->sort()
                    ->allow();

        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?sort=name"
            ),
            // expected sorting
            [
                new Sort('name', Sort::ORDER_ASC)
            ]
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_SortParameterIsValid
     */
    public function testFetch_SortParameterIsValid(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        array $expectedSorting = []
    )
    {
        $parameters = $fetcher->fetch($request);

        $this->assertEquals($expectedSorting, $parameters->getSorting(), "Wrong sort parameter");
    }

    public function dataProviderTestFetch_PaginationParameterInvalid()
    {
        $data = [];

        // CASE - EMPTY VALUE
        $fetcher = new ResourceCollectionParametersFetcher();
        $fetcher
            ->configure()
                ->pagination()
                    ->allow();
        
        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?range"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'range\' value has malformed format. Example of valid value: \'10-21\'.'
            .'  Expression requirements: \'\d+-\d+\''
        ];

        // CASE - WRONG FORMAT
        $wrongFormats = [
            "?range=10",
            "?range=-21",
            "?range=name"
        ];
        foreach ($wrongFormats as $format) {
            $data[] = [
                // fetcher
                $fetcher,
                // request
                Request::create(
                    $format
                ),
                // expected Exception
                'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
                // expected Exception message
                'Request parameter \'range\' value has malformed format. Example of valid value: \'10-21\'.'
                .'  Expression requirements: \'\d+-\d+\''
            ];
        }

        // CASE - WRONG RANGE VALUES
        $data[] = [
            // fetcher
            $fetcher,
            // request
            Request::create(
                "?range=20-10"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'range\' has not valid range value. Range start in bigger or equal to end'
        ];

        // CASE - EXCEED PAGINATION LIMIT
        $fetcher1 = new ResourceCollectionParametersFetcher();
        $fetcher1
            ->configure()
                ->pagination()
                    ->allow()
                    ->setPaginationMaxLimit(10);
        $data[] = [
            // fetcher
            $fetcher1,
            // request
            Request::create(
                "?range=10-100"
            ),
            // expected Exception
            'App\RestApiComponent\Response\Exception\RestRequestInvalidParameterException',
            // expected Exception message
            'Request parameter \'range\' value exceed pagination limit 10'
        ];

        return $data;
    }

    /**
     * @dataProvider dataProviderTestFetch_PaginationParameterInvalid
     */
    public function testFetch_PaginationParameterInvalid(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        $expectedException,
        $expectedExceptionMessage
    )
    {
        $this->assertFetchFail(
            $fetcher,
            $request,
            $expectedException,
            $expectedExceptionMessage
        );
    }

    protected function assertFetchSuccessful(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        Pagination $expectedPagination = null,
        Search $expectedSearch = null,
        array $expectedFilters = [],
        array $expectedSorting = []
    )
    {
        $parameters = $fetcher->fetch($request);
        
        $this->assertEquals($expectedFilters, $parameters->getFilters(), "Wrong filter parameter");
        $this->assertEquals(
            $expectedSearch ? $expectedSearch->getValue() : $expectedSearch,
            $parameters->getSearch(),
            "Wrong search parameter"
        );
        $this->assertEquals($expectedSorting, $parameters->getSorting(), "Wrong sort parameter");
        $this->assertEquals($expectedPagination, $parameters->getPagination(), "Wrong pagination parameter");
    }

    protected function assertFetchFail(
        ResourceCollectionParametersFetcher $fetcher,
        Request $request,
        $expectedException,
        $expectedExceptionMessage
    )
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $fetcher->fetch($request);
    }

}