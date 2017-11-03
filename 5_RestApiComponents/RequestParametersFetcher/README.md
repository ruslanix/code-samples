# Parameters Fecher : fetch and parse http query

The purpose of **Resource Collection Parameters Fetcher** - `ResourceCollectionParametersFetcher` class
(in further **RCPF**) is to easy fetch and parse Resource Collection URL Query parameters.

For instance we have the Request:

```

GET /users?q=williams&sort=name,date,status;desc=date&range=11-20&name=Max&email[like]=gmail.com&phone[null]&address[notnull]
```

It should return a list of the users according to these params:
1. `q=williams` search by word `williams`
2. `sort=name,date,status;desc=date` order by `name`, `date` and `status` where `date` should be in Desc order.
3. `range=11-20` return from 11th to 20th records (10 in total).
4. Filter by fields where
    - `name` is `Max`
    - `email` is **like** `*gmail.com`
    - `phone` is null
    - `address` is not null

It would be pretty hard to parse and use these parameters without the **RCPF**.

This is how **RCPF** will do that:

```php
<?
$fetcher = new ResourceCollectionParametersFetcher();

$fetcher
    ->configure()
        ->search()
            ->allow()
            ->setDefaultValue('Bob')
        ->sort()
            ->allow()
            ->setDefaultValue([
                new Parameter\Sort('name', Parameter\Sort::ORDER_ASC)
                // can be more the one for sort ...
            ])
        ->pagination()
            ->allow()
            ->setPaginationLimit(25)
            ->setDefaultValue(new Parameter\Pagination(0, 24))
        ->filter()
            ->setAllowedFilters([
                'state',  // all conditions allowed for 'state' field
                'name' => [Parameter\Filter::CONDITION_LIKE], // only 'like' condition allowed for 'name' field
                'phone',
                'address'
            ])
            ->setDefaultValue([
                new Parameter\Filter('state', Parameter\Filter::CONDITION_EQ, 'active'),
                new Parameter\Filter('name', Parameter\Filter::CONDITION_LIKE, 'John')
            ]);

/* @var \App\RestApiComponent\ResourceCollection\ParametersInterface */
// Request must be instance of 'Zend_Controller_Request_Http' or 'Symfony\Component\HttpFoundation\Request'
$parametersCollection = $fetcher->fetch($request);

```
and thats it.

## To access *"Search"* parameter (`q=williams`)
```php
<?
/* @var \App\RestApiComponent\Request\Parameter\Search */
$search = $parametersCollection->getSearch();
echo $search->getValue(); // williams
```

## To access *"Pagination"* parameter (`range=11-20`):
```php
<?
/* @var \App\RestApiComponent\Request\Parameter\Pagination */
$pagination = $parametersCollection->getPagination();
echo $pagination->getOffset(); // 11
echo $pagination->getLimit(); // 10
```

## To access *"Sorting"* parameters (`sort=name,date,status;desc=date`):
```php
<?
foreach ($parametersCollection->getSorting() as $sort) {
  /* @var \App\RestApiComponent\Request\Parameter\Sort[] */
  echo $sort->getField(); // name|date|status
  echo $sort->getOrder(); //ASC|DESC|ASC
}
```

## To access *"Filters"* parameters (`name=Max&email[like]=gmail.com&phone[null]&address[notnull]`):
```php
<?
/* @var \App\RestApiComponent\Request\Parameter\Filter[] */
$filters = $parametersCollection->getFilters();
foreach ($filters as $filter) {
  echo $filter->getField();      // name|email|phone|address
  echo $filter->getCondition()); // eq|like|null|notnull
  echo $filter->getValue();      // Max|gmail.com|null|null
}
```
