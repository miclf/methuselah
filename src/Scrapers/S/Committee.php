<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the pages of committees of the Senate.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Committee extends AbstractScraper
{
    /**
     * Character set of the scraped documents.
     *
     * @var string
     */
    protected $charset = 'ISO-8859-1';

    /**
     * Scrape the page of a committee and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        return [];
    }

    /**
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @param  string  $lang
     * @return array
     */
    protected function getProviderArguments($lang = 'fr')
    {
        $values = [
            'identifier' => $this->getOption('identifier'),
            'lang'       => $lang,
        ];

        return ['s.committee', $values];
    }
}
