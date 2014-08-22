<?php namespace Pandemonium\Methuselah\Scrapers;

use Pandemonium\Methuselah\Crawler\Crawler;
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
     * The options of the scraper.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Character set of the scraped documents.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

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
     * Get a DOM crawler prefilled with the whole document.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler()
    {
        $crawler = $this->newCrawler();

        // We explicitly specify the character set to avoid issues
        // with documents using old charsets such as ISO-8859-1.
        $crawler->addHtmlContent($this->getDocument(), $this->charset);

        return $crawler;
    }

    /**
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @return array
     */
    abstract protected function getProviderArguments();

    /**
     * Get the HTML of the document.
     *
     * @return string
     */
    public function getDocument()
    {
        list($pattern, $values) = $this->getProviderArguments();

        return $this->documentProvider->get($pattern, $values);
    }

    /**
     * Scrape data from a web page.
     *
     * @return mixed
     */
    abstract public function scrape();

    /**
     * Get the value of a single option of the scraper.
     *
     * @param  string  $key
     * @param  mixed   $default  Default value to return if the key does not exist
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    /**
     * Get the options of the scraper.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set a single option of the scraper.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return self
     */
    public function setOption($key, $value)
    {
        array_set($this->options, $key, $value);

        return $this;
    }

    /**
     * Set the options of the scraper.
     *
     * @param  array  $options
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Unset a single option of the scraper.
     *
     * @param  string  $key
     * @return self
     */
    public function unsetOption($key)
    {
        array_forget($this->options, $key);

        return $this;
    }

    /**
     * Remove all the options of the scraper.
     *
     * @return self
     */
    public function unsetOptions()
    {
        $this->options = [];

        return $this;
    }

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
     * Object oriented wrapper around preg_match().
     *
     * @param  string  $pattern
     * @param  string  $subject
     * @param  int     $offset
     * @return array
     */
    protected function match($pattern, $subject, $offset = 0)
    {
        $matches = [];

        preg_match($pattern, $subject, $matches, 0, $offset);

        return $matches;
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
                $value = $this->trimArray($value);
            } elseif (is_string($value)) {
                $value = $this->trim($value);
            }

            $array[$key] = $value;
        }

        return $array;
    }
}
