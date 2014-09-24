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
        $mp['identifier'] = $this->getOption('identifier');

        $mp += $this->getFullNameAndGroup();

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
     * Get the full name and political group of the MP.
     *
     * @return array
     */
    protected function getFullNameAndGroup()
    {
        // The first <th> of the table contains the full name
        // and the group, separated by an hyphen. We split
        // this string and only return the two parts.
        $str = $this->crawler->filter('th')->text();

        $parts = explode(' - ', $str);

        return [
            'given_name_surname' => trim($parts[0]),
            'political_group'    => trim($parts[1])
        ];
    }
}
