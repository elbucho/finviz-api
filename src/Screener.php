<?php

namespace Elbucho\FinvizApi;
use Elbucho\Config\Config;
use Elbucho\Config\InvalidFileException;
use Elbucho\Config\Loader\DirectoryLoader;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Screener
{
    /**
     * Current Filter list
     *
     * @access  private
     * @var     array
     */
    private $filters = [];

    /**
     * Filter configuration
     *
     * @access  private
     * @var     Config
     */
    private $config;

    /**
     * Page results
     *
     * @access  private
     * @var     Results
     */
    private $results;

    /**
     * Class constructor
     *
     * @access  public
     * @param   void
     * @return  Screener
     * @throws  InvalidFileException
     */
    public function __construct()
    {
        // Load the filter configuration
        $loader = new DirectoryLoader();
        $this->config = new Config(
            $loader->load(__DIR__ . '/config')
        );

        return $this;
    }

    /**
     * Add a filter to the filter list
     *
     * @access  public
     * @param   string  $category
     * @param   string  $option
     * @return  Screener
     */
    public function addFilter(string $category, string $option): Screener
    {
        $path = sprintf("filters.%s.%s", $category, $option);

        if ($this->config->exists($path)) {
            $this->filters[$category] = $this->config->get($path);
        }

        return $this;
    }

    /**
     * Search using the previously-defined filters and return results
     *
     * @access  public
     * @param   int     $page
     * @return  void
     * @throws  \Exception
     * @throws  GuzzleException
     */
    public function search(int $page = 0)
    {
        $this->results = null;
        $path = $this->buildPath($page);
        $client = new Client();
        $tries = 0;
        $status = 0;

        while ($tries < 3) {
            $response = $client->request('GET', $path);
            $status = (int) $response->getStatusCode();

            if ($status !== 200) {
                sleep(5);
                ++$tries;

                continue;
            }

            break;
        }

        if ( ! isset($response) or $status !== 200) {
            throw new \Exception(sprintf(
                'Unable to load results from path %s',
                $path
            ));
        }

        $this->results = new Results($response->getBody());
    }

    /**
     * Check to see if we are currently on the last page of results
     *
     * @access  public
     * @param   void
     * @return  bool
     */
    public function onLastPage(): bool
    {
        if ( ! isset($this->results)) {
            return false;
        }

        return $this->results->onLastPage();
    }

    /**
     * Check to see if we are currently on the first page of results
     *
     * @access  public
     * @param   void
     * @return  bool
     */
    public function onFirstPage(): bool
    {
        if ( ! isset($this->results)) {
            return false;
        }

        return $this->results->onFirstPage();
    }

    /**
     * Load the next page of results
     *
     * @access  public
     * @param   void
     * @return  void
     * @throws  \Exception
     * @throws  GuzzleException
     */
    public function loadNextPage()
    {
        $nextPage = $this->results->getNextPage();
        $this->search($nextPage);
    }

    /**
     * Load the previous page of results
     *
     * @access  public
     * @param   void
     * @return  void
     * @throws  \Exception
     * @throws  GuzzleException
     */
    public function loadPreviousPage()
    {
        $prevPage = $this->results->getPreviousPage();
        $this->search($prevPage);
    }

    /**
     * Return the results from the query
     *
     * @access  public
     * @param   void
     * @return  Results|null
     */
    public function getResults(): ?Results
    {
        if ( ! $this->results instanceof Results) {
            return null;
        }

        return $this->results;
    }

    /**
     * Build the path
     *
     * @access  private
     * @param   int     $page
     * @return  string
     * @throws  \Exception
     */
    private function buildPath(int $page = 0): string
    {
        $filters = $this->buildFilters();
        $path = $this->config->get('site.path', false);

        if ( ! $path) {
            throw new \Exception(
                'No site.path is configured'
            );
        }

        $path .= '&' . $filters;

        if ( ! empty($page)) {
            $path .= '&r=' . $page;
        }

        return $path;
    }

    /**
     * Build a url string of provided filters
     *
     * @access  private
     * @param   void
     * @return  string
     */
    private function buildFilters()
    {
        $return = '';

        if (count($this->filters) > 0) {
            $return = 'f=' . implode(',', array_values($this->filters));
        }

        return $return;
    }
}