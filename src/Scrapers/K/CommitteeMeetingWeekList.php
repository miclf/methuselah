<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract links to current agenda pages
 * of weeks of committee meetings.
 *
 * Each of the returned URLs targets a page listing
 * all the meetings of committees (or group of
 * committees) that are planned for the week.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeMeetingWeekList extends AbstractScraper
{
    /**
     * List of patterns to extract date
     * ranges from week labels.
     *
     * @var array
     */
    protected $weekLabelPatterns = [
        // Normal case. Examples:
        // ‘Semaine du lundi 10 février 2015 au vendredi 14 février 2015’
        // ‘Week van maandag 10 februari 2015 tot vrijdag 14 februari 2015’
        'week' => [
            'regex'   => '#du\s+(?:\w+)\s+(\d{1,2})(?:er)?\s+(\w+)\s+(\d{4})\s+au\s+(?:.+)\s+(\d{1,2})(?:er)?\s+(\w+)\s+(\d{4})#u',
            'matches' => [
                'startDay' => 1, 'startMonth' => 2, 'startYear' => 3,
                'endDay'   => 4, 'endMonth'   => 5, 'endYear'   => 6,
            ],
        ],
        // Case where the year is missing for the first date. Examples:
        // ‘Semaine du lundi 10 février au vendredi 14 février 2015’
        // ‘Week van maandag 10 februari tot vrijdag 14 februari 2015’
        'week_with_missing_year' => [
            'regex'   => '#du (?:\w+) (\d{1,2}) (\w+) au (?:.+) (\d{1,2}) (\w+) (\d{4})#u',
            'matches' => [
                'startDay' => 1, 'startMonth' => 2, 'startYear' => 5,
                'endDay'   => 3, 'endMonth'   => 4, 'endYear'   => 5,
            ],
        ],
        // Case where only the first half of the range is present. Examples:
        // ‘Semaine du lundi 10 octobre’
        // ‘Week van maandag 10 oktober’
        'week_with_missing_end_of_range' => [
            'regex'   => '#du (?:\w+) (\d{1,2}) (\w+)#u',
            'matches' => [
                'startDay' => 1, 'startMonth' => 2
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
        $identifiers = [];

        foreach ($this->getAgendaAnchors() as $node) {

            if (!$matches = $this->matchCommitteeWeek($node)) continue;

            list($startDate, $endDate) = $this->getDateRange($node->text());

            $identifiers[] = [
                'identifier' => $matches[1],
                'startDate'  => $startDate,
                'endDate'    => $endDate,
                'url'        => $this->makeAgendaUrl($matches[1]),
            ];
        }

        // Remove null values and reset indices.
        return array_values(array_filter($identifiers));
    }

    /**
     * Return the HTML anchors in the main
     * content area of the document.
     */
    protected function getAgendaAnchors()
    {
        return $this->getCrawler()->find('#content a');
    }

    /**
     * Get a DOM crawler prefilled with the whole document.
     *
     * @param  string  $document
     * @param  string  $charset
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler($document = null, $charset = null)
    {
        if ($crawler = $this->getOption('crawler')) {
            return $crawler;
        }

        return parent::getCrawler();
    }

    /**
     * Check if a given anchor targets the
     * agenda page of a committee week.
     *
     * @param  \QueryPath\DOMQuery  $anchor
     * @return array
     */
    protected function matchCommitteeWeek(\QueryPath\DOMQuery $anchor)
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
