<?php namespace Pandemonium\Methuselah\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;

/**
 * Base class for all scrapers.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractScraper
{
    /**
     * A document provider.
     *
     * @var \Pandemonium\Methuselah\DocumentProvider
     */
    protected $documentProvider;

    /**
     * Constructor.
     *
     * @param  \Pandemonium\Methuselah\DocumentProvider  $documentProvider
     * @return self
     */
    public function __construct(DocumentProvider $documentProvider)
    {
        $this->documentProvider = $documentProvider;
    }

    /**
     * Scrape data from a web page.
     *
     * @param  array  $options
     * @return mixed
     */
    abstract public function scrape(array $options = null);

    /**
     * Get the document provider of the scraper.
     *
     * @return \Pandemonium\Methuselah\DocumentProvider
     */
    public function getDocumentProvider()
    {
        return $this->documentProvider;
    }

    /**
     * Create a new DomCrawler.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function newCrawler()
    {
        return new Crawler;
    }

    /**
     * Recursive extended trim utility method to trim arrays of strings.
     *
     * @param  array  $array
     * @return array
     */
    protected function trimArray(array $array)
    {
        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $array[$key] = $this->trimArray($value);
            } else {
                $array[$key] = $this->trim($value);
            }
        }

        return $array;
    }

    /**
     * Extended trim utility method to deal with some crazy use cases that can
     * be found on official websites.
     *
     * @param  string  $str
     * @return string
     */
    protected function trim($str)
    {
        $regex = [
            // Replace non-breaking spaces by normal spaces
            '# #'      => ' ',
            // Replace multiple adjacent spaces by a single one
            '#\s{2,}#' => ' ',
        ];

        $str = preg_replace(array_keys($regex), array_values($regex), $str);

        // Quickly trim the string (faster than regex)
        return trim($str);
    }
}
