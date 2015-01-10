<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract links to current week agenda pages
 * of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeAgendaList extends AbstractScraper
{
    /**
     * List of patterns to extract date
     * ranges from week labels.
     *
     * @var array
     */
    protected $weekLabelPatterns = [
        'week' => [
            'regex'   => '#du (?:\w+) (\d{1,2}) (\w+) (\d{4}) au (?:.+) (\d{1,2}) (\w+) (\d{4})#',
            'matches' => [
                'startDay' => 1, 'startMonth' => 2, 'startYear' => 3,
                'endDay'   => 4, 'endMonth'   => 5, 'endYear'   => 6,
            ],
        ],
    ];

    /**
     * Scrape the page of committee agenda lists
     * to find links to week agenda pages.
     *
     * @return array
     */
    public function scrape()
    {
        $anchors = $this->getAgendaAnchors()->each(function ($node) {

            if (!$matches = $this->matchCommitteeWeek($node)) return;

            list($startDate, $endDate) = $this->getDateRange($node->text());

            return [
                'identifier' => $matches[1],
                'startDate'  => $startDate,
                'endDate'    => $endDate,
                'url'        => $this->makeAgendaUrl($matches[1]),
            ];
        });

        // Remove null values and reset indices.
        return array_values(array_filter($anchors));
    }

    /**
     * Return the HTML anchors in the main
     * content area of the document.
     *
     * @return \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected function getAgendaAnchors()
    {
        return $this->getCrawler()->filter('#content a');
    }

    /**
     * Check if a given anchor targets the
     * agenda page of a committee week.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return array
     */
    protected function matchCommitteeWeek(Crawler $anchor)
    {
        // This pattern captures the identifier of the committee week.
        $pattern = '#pat=PROD-commissions&week=(\d+)#';

        return $this->match($pattern, $anchor->attr('href'));
    }

    /**
     * Build an absolute agenda page URL from an identifier.
     *
     * @param  string  $identifier
     * @return string
     */
    protected function makeAgendaUrl($identifier)
    {
        return
            'http://www.lachambre.be/kvvcr/showpage.cfm'.
            '?section=none&language=fr&cfm=/site/wwwcfm/agenda/comagendaWeek.cfm'.
            '?pat=PROD-commissions&week='.$identifier;
    }

    /**
     * Extract a range of dates from a string.
     *
     * This returns an array of two ISO 8601 dates. The
     * first one represents the beginning of the range
     * and the second one represents its end date.
     *
     * @param  string  $str
     * @return array
     *
     * @throws \Exception if no date range is found.
     */
    protected function getDateRange($str)
    {
        foreach ($this->weekLabelPatterns as $pattern) {

            $matches = $this->match($pattern['regex'], $str);

            if (!$matches) continue;

            // Create local variables from to their corresponding matches.
            extract($this->mapMatches($pattern['matches'], $matches));

            return [
                $this->formatDate($startYear, $startMonth, $startDay),
                $this->formatDate($endYear, $endMonth, $endDay),
            ];
        }

        throw new Exception("Could not find any date range in [$str]");
    }

    /**
     * Map a set of key names with a series of matched values.
     *
     * @param  array  $map      Array of keys, each one having the number
     *                          of its related match as a value
     * @param  array  $matches  Array of data to map with the keys
     * @return array
     */
    protected function mapMatches(array $map, array $matches)
    {
        foreach ($map as $name => $matchIndex) {
            $map[$name] = $matches[$matchIndex];
        }

        return $map;
    }

    /**
     * Format date components into an ISO 8601 date.
     *
     * @param  int         $year   Year part of the date
     * @param  int|string  $month  Number or name of the month
     * @param  int         $day    Day
     * @return string
     */
    protected function formatDate($year, $month, $day)
    {
        if (!ctype_digit($month)) {
            $month = $this->getMonthNumber($month);
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Get the number of a month from its French name.
     *
     * @param  string  $name
     * @return int
     */
    protected function getMonthNumber($name)
    {
        $monthNames = [
            'janvier', 'février',  'mars',
            'avril',   'mai',      'juin',
            'juillet', 'août',     'septembre',
            'octobre', 'novembre', 'décembre',
        ];

        // Month numbers start at 1 and not 0, so we need
        // to increment the array index that we get.
        return array_search($name, $monthNames) + 1;
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
        return ['k.agenda_list.committee_weeks', []];
    }
}
