<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Illuminate\Container\Container;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract identifiers of current agenda pages
 * of detailed committees meetings.
 *
 * Each of the returned IDs targets a page detailing
 * the meetings of a given committee (or group of
 * committees) that are planned for the week.
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
     * When we reach the main page of the committee agenda,
     * we can face three different types of content:
     *
     *   1. A list of multiple weeks of meetings
     *   2. A series of meetings for a given week
     *   3. A page listing meetings of a specific committee
     *
     * This scraper detects the type of content that is on the page and
     * acts accordingly. The returned result is always supposed to be
     * a list of IDs, each one targeting a page listing meetings
     * for a specific committee (or group of committees).
     *
     * @return array
     */
    public function scrape()
    {
        // Store a crawler for the main page of committee agenda.
        // We will reuse it later in a way that will depend on
        // the type of content it stores.
        $this->crawler = $this->getCrawler();

        $commAgendaIds = [];

        // Case 3: a page listing meetings of a specific committee.
        if ($this->isSingleCommitteeAgenda()) {
            // Not implemented yet.
        }

        if ($this->isListOfWeeks()) {
            // Case 1: a list of multiple weeks of meetings.
            $commAgendaIds = $this->getAgendasForMultipleWeeks();
        } else {
            // Case 2: a series of meetings for a given week.
            $commAgendaIds = $this->getAgendasForSingleWeek();
        }

        return $commAgendaIds;
    }

    /**
     * Check if the current page lists meetings
     * of a specific committee.
     *
     * @return bool
     */
    protected function isSingleCommitteeAgenda()
    {
        // TODO: implement this.
        return false;
    }

    /**
     * Check if the current page contains a
     * list of multiple weeks of meetings.
     *
     * @return bool
     */
    protected function isListOfWeeks()
    {
        $selector = "h3:contains('TABLEAU DES REUNIONS DE COMMISSION')";

        $span = $this->crawler->filter($selector);

        return (bool) count($span);
    }

    /**
     * From a list of multiple weeks of meetings, get a list
     * of IDs of meeting pages of specific committees.
     *
     * @return array
     */
    protected function getAgendasForMultipleWeeks()
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
        $scraper = $this->makeScraper('CommitteeMeetingWeekList');

        // We already have a crawler storing the content
        // to scrape, so we inject it to avoid uselessly
        // downloading this content one more time.
        $scraper->setOptions(['crawler' => $this->crawler]);

        $data = $scraper->scrape();

        // The scraper gives us a bunch of data about each week. We extract
        // the URLs because that’s the only info we’re interested in.
        $weekUrls = array_pluck($data, 'url');

        return $this->sort($weekUrls);
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
     * Scrape an array of IDs from a given DOM crawler
     * listing a series of meetings for a given week.
     *
     * @return array
     */
    protected function getAgendasForSingleWeek()
    {
        $scraper = $this->makeScraper('CommitteeMeetingList');

        // Since this class’ DOM crawler already stores the content
        // we want to scrape, we will simply pass it, thus avoiding
        // to uselessly download the page once again.
        $scraper->setOptions(['crawler' => $this->crawler]);

        $identifiers = $scraper->scrape();

        return $this->sort($identifiers);
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
     * Set up a DOM crawler and fill it with the given document.
     *
     * @param  string  $document
     * @param  string  $charset
     * @return \QueryPath\DOMQuery
     */
    protected function buildCrawler($document, $charset = null)
    {
        if (is_null($charset)) {
            $charset = $this->charset;
        }

        // Convert the content of the document to UTF-8 in case it
        // uses another encoding (I’m looking at you, ISO-8859-1).
        if ($charset !== 'UTF-8') {
            $document = mb_convert_encoding($document, 'UTF-8', $charset);
        }

        // Create an HTML5-aware crawler.
        return \html5qp($document);
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
        return ['k.agenda_list.committee', []];
    }
}
