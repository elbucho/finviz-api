# finviz-api

This project provides a programmable interface to the 
FinViz stock screener website (https://finviz.com/screener.ashx).  
You can provide any number of filters and search the screener, and
it will return an array of symbols that match your criteria.

###Setup

You can create an instance of this object by including the following code:

```
$screener = new Elbucho\FinvizApi\Screener(); 
```

Once you have instantiated it, you can provide any number of filters:

### Filters

The available filter categories and options are listed in the config/filters.yml file.
Any of the filters provided can be used:

```
$screener
    ->addFilter('float', 'under_50m')
    ->addFilter('price', 'over_5');
```

### Results

Once you have provided the required filters, you can use the search() command to populate an internal list
of results.  To display these, use "getResults()":

```
$screener->search();

foreach ($screener->getResults() as $result) {
    var_dump($result);
}
```

This will provide a multidimensional array containing the following information:

```
[
    'ticker'      => Stock ticker symbol,
    'company'     => Company name,
    'sector'      => Sector (eg. Technology, Healthcare, etc),
    'industry'    => Industry within sector (eg. REIT - Industrial),
    'market_cap'  => Total market cap (int),
    'price'       => Current price in USD,
    'change'      => Percentage change from previously reported price (+/-),
    'volume'      => Most recent volume of trades
],
...
```

### Pagination

You can determine whether you are on the first page with:

```
$screener->onFirstPage();
```

Similarly, you can determine whether you are on the last page with:

```
$screener->onLastPage();
```

You can advance to the next page results with:

```
$screener->loadNextPage();
```

And you can decrement your page number with:

```
$screener->loadPreviousPage();
```

Here is a sample application that loops through all of the results
of a search for stocks that are on the NYSE, that have a float of at least
20m shares, that have a price below $5, and that show a recent trade volume
at least 3 times higher than its recent average:

```
$screener = new Elbucho\FinvizApi\Screener();
$screener
    ->addFilter('exchange', 'nyse')
    ->addFilter('float', 'under_20m')
    ->addFilter('price', 'under_5')
    ->addFilter('relative_volume', 'over_3')
    ->search();

while (true) {
    $results = $screener->getResults();

    foreach ($results as $result) {
        printf(
            "%s:\tCap: %s\tPrice: %s\tChange: %s\tVolume: %s\n",
            $result['ticker'],
            $result['market_cap'],
            $result['price'],
            $result['change'],
            $result['volume']
        );
    }

    if ($screener->onLastPage()) {
        break;
    }

    $screener->loadNextPage();
}
```