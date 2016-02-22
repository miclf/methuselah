<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Illuminate\Container\Container;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract identifiers of current agenda pages
 * of detailed meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractAgendaListScraper extends AbstractScraper
{
    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * The scraper to use to get data from individual weeks.
     *
     * @var string
     */
    protected $meetingListScraper;

    /**
     * The key to request from the URL repository.
     *
     * @var string
     */
    protected $urlRepositoryKey;

    /**
     * Scrape an agenda to find identifiers of pages of meetings.
     *
     * @return array
     */
    public function scrape()
    {
        $agendaIds = [];

        foreach ($this->getWeekUrls() as $weekUrl) {
            $urls = $this->getAgendaIds($weekUrl);
            $agendaIds = array_merge($urls, $agendaIds);
        }

        return $this->sort($agendaIds);
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
    protected function getAgendaIds($weekUrl)
    {
        $scraper = $this->makeScraper($this->meetingListScraper);

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
        return [$this->urlRepositoryKey, []];
    }
}
