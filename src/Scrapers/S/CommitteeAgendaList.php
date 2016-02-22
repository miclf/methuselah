<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Illuminate\Container\Container;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract identifiers of current agenda pages
 * of detailed committee meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeAgendaList extends AbstractScraper
{
    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * Scrape the committee agenda to find identifiers
     * of pages of committee meetings.
     *
     * @return array
     */
    public function scrape()
    {
        $commAgendaIds = [];

        foreach ($this->getWeekUrls() as $weekUrl) {
            $urls = $this->getCommitteeAgendaIds($weekUrl);
            $commAgendaIds = array_merge($urls, $commAgendaIds);
        }

        return $this->sort($commAgendaIds);
    }

    /**
     * Scrape an array of URLs from a list
     * of multiple weeks of meetings.
     *
     * @return array
     */
    protected function getWeekUrls()
    {
        return [
            'week'      => 'http://senat.be/www/?MIval=/Agenda/Week&when=week&LANG=fr',
            'next_week' => 'http://senat.be/www/?MIval=/Agenda/Week&when=next_week&LANG=fr',
        ];
    }

    /**
     * Scrape an array of identifiers from a given page
     * listing a series of meetings for a given week.
     *
     * @param  string  $weekUrl
     * @return array
     */
    protected function getCommitteeAgendaIds($weekUrl)
    {
        $scraper = $this->makeScraper('CommitteeMeetingList');

        $scraper->setOptions(['url' => $weekUrl]);

        return $scraper->scrape();
    }

    /**
     * Instantiate an agenda scraper of the given class.
     *
     * @param  string  $class
     * @return \Pandemonium\Methuselah\Scrapers\ScraperInterface
     */
    protected function makeScraper($class)
    {
        $fullClassName = __NAMESPACE__.'\\'.$class;

        return (new Container)->make($fullClassName);
    }

    /**
     * Sort an array by value.
     *
     * @param  array  $array
     * @return array
     */
    protected function sort(array $array)
    {
        sort($array);

        return $array;
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
    protected function getProviderArguments()
    {
        return ['s.agenda_list.committee_weeks', []];
    }
}
