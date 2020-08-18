<?php

include_once('vendor/autoload.php');

$screener = new Elbucho\FinvizApi\Screener();
$screener
    ->addFilter('float', 'under_20m')
    ->addFilter('price', 'under_5')
    ->addFilter('relative_volume', 'over_3')
    ->search();

$results = $screener->getResults();

while (true) {
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
