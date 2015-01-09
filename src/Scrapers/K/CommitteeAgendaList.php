<?php namespace Pandemonium\Methuselah\Scrapers\K;

/**
 * Extract links to current week agenda pages
 * of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeAgendaList extends AbstractScraper
{
    /**
     * Scrape the page of committee agenda lists
     * to find links to week agenda pages.
     *
     * @return array
     */
    public function scrape() {}

    /**
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @param  string  $lang
     * @return array
     */
    protected function getProviderArguments()
    {
        return ['k.agenda_list.committee_weeks', []];
    }
}
