<?php namespace Pandemonium\Methuselah\Scrapers\K;

/**
 * Extract data from the list of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeList extends AbstractScraper
{
    /**
     * Scrape the pages of lists of committees
     * and extract their information.
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
        return ['k.committee_list', ['lang' => $lang]];
    }
}
