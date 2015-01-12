<?php namespace Pandemonium\Methuselah\Scrapers;

use Pandemonium\Methuselah\Crawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;

/**
 * Base class for all scrapers.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractScraper implements ScraperInterface
{
    use ScrapingHelpersTrait;

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
     * @param  string  $document
     * @param  string  $charset
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler($document = null, $charset = null)
    {
        if (is_null($document)) $document = $this->getDocument();
        if (is_null($charset))  $charset  = $this->charset;

        $crawler = $this->newCrawler();

        // We explicitly specify the character set to avoid issues
        // with documents using old charsets such as ISO-8859-1.
        $crawler->addHtmlContent($document, $charset);

        return $crawler;
    }

    /**
     * Get a prefilled DOM crawler from a given location.
     *
     * @param  string  $source   A local path or remote URL
     * @param  string  $charset
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawlerFrom($source, $charset = null)
    {
        $document = $this->documentProvider->getFrom($source);

        if (is_null($charset)) {
            $charset = $this->charset;
        }

        $crawler = $this->newCrawler();

        // We explicitly specify the character set to avoid issues
        // with documents using old charsets such as ISO-8859-1.
        $crawler->addHtmlContent($document, $charset);

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
    public function getDocument($pattern = null, $values = null)
    {
        if (is_null($pattern)) {
            list($pattern, $values) = $this->getProviderArguments();
        }

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
}
