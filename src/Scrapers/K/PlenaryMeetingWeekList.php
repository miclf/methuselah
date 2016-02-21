<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data of current agenda pages of weeks
 * of plenary sessions of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class PlenaryMeetingWeekList extends AbstractScraper
{
    /**
     * List of patterns to extract date
     * ranges from week labels.
     *
     * @var array
     */
    protected $weekLabelPatterns = [
        // Normal case. Examples:
        // ‘Semaine du lundi 1er février 2015 au vendredi 5 février 2015’
        // ‘Semaine du lundi 15 février 2015 au vendredi 19 février 2015’
        'week' => [
            'regex'   => '#du (\d{1,2})(?:er)? au (\d{1,2})(?:er)? (\w+) (\d{4})#u',
            'matches' => [
                'startDay' => 1, 'startMonth' => 3, 'startYear' => 4,
                'endDay'   => 2, 'endMonth'   => 3, 'endYear'   => 4,
            ],
        ],
        // Case where a range starts in a month and ends in the next one.
        // Example:
        // ‘Semaine du lundi 28 janvier 2015 au vendredi 1er février 2015’
        'week_overlapping_two_months' => [
            'regex'   => '#du (\d{1,2})(?:er)? (\w+) au (\d{1,2})(?:er)? (\w+) (\d{4})#u',
            'matches' => [
                'startDay' => 1, 'startMonth' => 2, 'startYear' => 5,
                'endDay'   => 3, 'endMonth'   => 4, 'endYear'   => 5,
            ],
        ],
    ];

    /**
     * Scrape the page of plenary agenda lists
     * to find links to agenda pages.
     *
     * @return array
     *
     * @throws \Exception if no date range is found in a matching anchor.
     */
    public function scrape()
    {
        $anchors = $this->getAgendaAnchors()->each(function ($node) {

            if (!$matches = $this->matchPlenaryWeek($node)) return;

            list($startDate, $endDate) = $this->getDateRange($node->text());

            return [
                'identifier' => $matches[1],
                'startDate'  => $startDate,
                'endDate'    => $endDate,
            ];
        });

        return $this->removeInvalidAnchors($anchors);
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
     * agenda page of a plenary week.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return array
     */
    protected function matchPlenaryWeek(Crawler $anchor)
    {
        if (!str_contains($anchor->text(), 'Semaine')) {
            return [];
        }

        // This pattern captures the identifier of the plenary week.
        $pattern = '#pat=PROD-Plenum&plen=(\d+_\d+)&type=full#';

        return $this->match($pattern, $anchor->attr('href'));
    }

    /**
     * Remove invalid anchors to plenary week agendas.
     *
     * Invalid anchors are duplicate ones as well as those
     * that have been replaced by newer versions.
     *
     * @param  array  $anchors
     * @return array
     */
    protected function removeInvalidAnchors(array $anchors)
    {
        $anchors = $this->removeDuplicates($anchors);

        return $this->removeOutdatedAnchors($anchors);
    }

    /**
     * Remove duplicates from an array of anchors.
     *
     * @param  array  $anchors
     * @return array
     */
    protected function removeDuplicates(array $anchors)
    {
        $identifiers = [];

        return array_filter($anchors, function ($anchor) use (&$identifiers) {

            if (is_null($anchor) || in_array($anchor['identifier'], $identifiers)) {
                return false;
            }

            $identifiers[] = $anchor['identifier'];

            return true;
        });
    }

    /**
     * Keep only the most recent anchor for each
     * week in an array of week anchors.
     *
     * @param  array  $anchors
     * @return array
     */
    protected function removeOutdatedAnchors(array $anchors)
    {
        $valid    = [];
        $versions = [];

        foreach ($anchors as $anchor) {

            list($weekNumber, $version) = explode('_', $anchor['identifier']);

            // If there is no registered version for the current anchor yet or
            // if this anchor’s version is newer than the currently registered
            // one, we’ll keep the current anchor as the new most recent one.
            if (
                !isset($versions[$weekNumber]) ||
                $version > $versions[$weekNumber]
            ) {
                $versions[$weekNumber] = $version;
                $valid[$weekNumber]    = $anchor;
            }
        }

        return array_values($valid);
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
        $pattern = 'k.agenda_list.plenary';
        $value   = [];

        if ($identifier = $this->getOption('_identifier')) {
            $pattern = 'k.agenda_list.plenary_custom';
            $value   = ['identifier' => $identifier];
        }

        return [$pattern, $value];
    }
}
