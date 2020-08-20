<?php

namespace Elbucho\FinvizApi;

class Results implements \Iterator
{
    /**
     * Symbols in current results table
     *
     * @access  private
     * @var     array
     */
    private $results;

    /**
     * Previous page
     *
     * @access  private
     * @var     int
     */
    private $pagePrev = 0;

    /**
     * Next page
     *
     * @access  private
     * @var     int
     */
    private $pageNext = 0;

    /**
     * Class constructor
     *
     * @access  public
     * @param   string  $body
     * @return  Results
     * @throws  \Exception
     */
    public function __construct(string $body = null)
    {
        if ( ! is_null($body)) {
            $this->parseBody($body);
        }

        return $this;
    }

    /**
     * Determine whether we are currently on the first page
     *
     * @access  public
     * @param   void
     * @return  bool
     */
    public function onFirstPage(): bool
    {
        return $this->pagePrev == 0;
    }

    /**
     * Determine whether we are currently on the last page
     *
     * @access  public
     * @param   void
     * @return  bool
     */
    public function onLastPage(): bool
    {
        return $this->pageNext == 0;
    }

    /**
     * Return the next page
     *
     * @access  public
     * @param   void
     * @return  int
     */
    public function getNextPage(): int
    {
        return $this->pageNext;
    }

    /**
     * Return the previous page
     *
     * @access  public
     * @param   void
     * @return  int
     */
    public function getPreviousPage(): int
    {
        return $this->pagePrev;
    }

    /**
     * Parse the current page
     *
     * @access  private
     * @param   string  $body
     * @return  void
     * @throws  \Exception
     */
    private function parseBody(string $body)
    {
        $document = new \DOMDocument();

        if ( ! @$document->loadHTML($body)) {
            throw new \Exception('Unable to load the results');
        }

        // Stock symbols
        $finder = new \DOMXPath($document);
        $symbols = $finder->query(
            "//*[contains(@class, 'screener-body-table-nw')]/parent::tr"
        );

        $this->loadSymbols($symbols);

        // Pages
        $pages = $finder->query(
            "//*[contains(@class, 'screener_pagination')]/a"
        );

        $this->loadPages($pages);
    }

    /**
     * Load the symbols from the parsed HTML
     *
     * @access  private
     * @param   \DOMNodeList    $nodes
     * @return  void
     */
    private function loadSymbols(\DOMNodeList $nodes)
    {
        /* @var \DOMNode $tr */
        foreach ($nodes as $tr) {
            if ( ! $tr->hasChildNodes()) {
                continue;
            }

            $tdIndex = 0;
            $td = $tr->firstChild;
            $symbol = [];

            while (true) {
                $nextSibling = $td->nextSibling;

                if ($td->nodeName == 'td') {
                    switch($tdIndex) {
                        case 1:
                            $symbol['ticker'] = $this->getText($td->nodeValue);
                            break;
                        case 2:
                            $symbol['company'] = $this->getText($td->nodeValue);
                            break;
                        case 3:
                            $symbol['sector'] = $this->getText($td->nodeValue);
                            break;
                        case 4:
                            $symbol['industry'] = $this->getText($td->nodeValue);
                            break;
                        case 6:
                            $symbol['market_cap'] = $this->getText($td->nodeValue);
                            break;
                        case 8:
                            $symbol['price'] = $this->getText($td->nodeValue);
                            break;
                        case 9:
                            $symbol['change'] = $this->getText($td->nodeValue);
                            break;
                        case 10:
                            $symbol['volume'] = $this->getText($td->nodeValue);
                            break;
                    }

                    $tdIndex++;
                }

                if (is_null($nextSibling)) {
                    break;
                }

                $td = $nextSibling;
            }

            $this->results[] = $symbol;
        }
    }

    /**
     * Determine what the next and previous pages are
     *
     * @access  private
     * @param   \DOMNodeList    $nodes
     * @return  void
     */
    private function loadPages(\DOMNodeList $nodes)
    {
        $this->pagePrev = 0;
        $this->pageNext = 0;

        /* @var \DOMNode $link */
        foreach ($nodes as $link) {
            $value = strtolower($this->getText($link->nodeValue));

            if ($value == 'prev') {
                $this->pagePrev = $this->getPageNumber($link);
            }

            if ($value == 'next') {
                $this->pageNext = $this->getPageNumber($link);
            }
        }
    }

    /**
     * Return the page number for a given A tag
     *
     * @access  private
     * @param   \DOMNode    $link
     * @return  int
     */
    private function getPageNumber(\DOMNode $link): int
    {
        /* @var \DOMAttr $attribute */
        foreach ($link->attributes as $attribute) {
            if ($attribute->nodeName == 'href') {
                $url = $attribute->nodeValue;

                preg_match("/&r=(?P<page>\d+)(\s*)$/", $url, $match);

                if ( ! empty($match['page'])) {
                    return (int) $match['page'];
                }
            }
        }

        return 0;
    }

    /**
     * Return only the text from a given node
     *
     * @access  private
     * @param   string  $value
     * @return  string
     */
    private function getText(string $value): string
    {
        preg_match("/^([0-9]+)\.([0-9]+)M$/", $value, $match);

        if ( ! empty($match[1])) {
            $secondary = ((empty($match[2]) or (strlen($match[2]) !== 2)) ? '00' : $match[2]);
            $value = sprintf("%s%s0000", $match[1], $secondary);
        }

        return preg_replace(
            [
                "/\$/",
                "/%/",
                "/,/"
            ],
            '',
            $value
        );
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->results);
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->results);
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return string|float|int|bool|null scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->results);
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $key = key($this->results);

        return ($key !== null and $key !== false);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->results);
    }
}