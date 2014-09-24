<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the pages of senators.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MP extends AbstractScraper
{
    /**
     * Character set of the scraped documents.
     *
     * @var string
     */
    protected $charset = 'ISO-8859-1';

    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * Scrape the page of a senator and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        $mp = [];

        $this->crawler = $this->getCrawler();

        // Extract relevant data from the different parts of the page.
        $mp['given_name_surname'] = $this->getFullName();
        $mp['identifier']         = $this->getOption('identifier');
        $mp['party']              = $this->getParty();

        return $mp;
    }

    /**
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @return array
     */
    protected function getProviderArguments()
    {
        return ['s.mp', ['identifier' => $this->getOption('identifier')]];
    }

    /**
     * Get the given name and surname of the MP.
     *
     * @return string
     */
    protected function getFullName()
    {
        return $this->crawler->filter('title')->text();
    }

    /**
     * Get the party of the MP.
     *
     * @return string
     */
    protected function getParty()
    {
        // The first <th> of the table contains the full name
        // and the party, separated by an hyphen. We split
        // this string and only return the party name.
        $str = $this->crawler->filter('th')->text();

        $party = explode('-', $str)[1];

        return trim($party);
    }
}
