<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Illuminate\Container\Container;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract identifiers of current agenda pages
 * of plenary sessions of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class PlenaryAgendaList extends AbstractScraper
{
    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * Scrape the plenary agenda to find identifiers
     * of pages of plenary meetings.
     *
     * When we reach the main page of the plenary agenda,
     * we can face two different types of content:
     *
     *   1. A list of multiple weeks of plenary sessions
     *   2. A page with one or more plenaries for a given week
     *
     * This scraper detects the type of content that is on the page and
     * acts accordingly. The returned result is always supposed to be
     * a list of IDs or the 'self' keyword. Each ID targets a page
     * listing one or more plenary sessions.
     *
     * @return array
     */
    public function scrape()
    {
        $this->crawler = $this->getCrawler();

        // Case 1: a list of multiple weeks of plenary sessions.
        if ($this->isListOfWeeks()) {
            return $this->getAgendasForMultipleWeeks();
        }

        // TODO: handle case where no plenary is scheduled at all.

        // Case 2: a page with one or more plenaries for a given week.
        return $plenaryAgendaIds = ['self'];
    }

    /**
     * Check if the current page contains a
     * list of multiple weeks of plenaries.
     *
     * @return bool
     */
    protected function isListOfWeeks()
    {
        $selector = "td>a:contains('Semaine du ')";

        $span = $this->crawler->filter($selector);

        return (bool) count($span);
    }

    /**
     * From a list of multiple weeks of plenaries, get a
     * list of IDs of agenda pages of plenary sessions.
     *
     * @return array
     */
    protected function getAgendasForMultipleWeeks()
    {
        $scraper = $this->makeScraper('PlenaryMeetingWeekList');

        // We already have a crawler storing the content
        // to scrape, so we inject it to avoid uselessly
        // downloading this content one more time.
        $scraper->setOptions(['crawler' => $this->crawler]);

        // The scraper gives us a bunch of data about each week. We extract
        // the IDs because that’s the only info we’re interested in.
        $weekIdentifiers = array_pluck($scraper->scrape(), 'identifier');

        sort($weekIdentifiers);

        return $weekIdentifiers;
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
        $pattern = 'k.agenda_list.plenary';
        $value   = [];

        if ($identifier = $this->getOption('_identifier')) {
            $pattern = 'k.agenda_list.plenary_custom';
            $value   = ['identifier' => $identifier];
        }

        return [$pattern, $value];
    }
}
