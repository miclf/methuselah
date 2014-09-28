<?php namespace Pandemonium\Methuselah\Scrapers;

/**
 * Helper methods for scraping operations.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
trait ScrapingHelpersTrait
{
    /**
     * The list of French month names and their associated number.
     *
     * @var array
     */
    protected $months = [
        'janvier'   => '01',
        'février'   => '02',
        'mars'      => '03',
        'avril'     => '04',
        'mai'       => '05',
        'juin'      => '06',
        'juillet'   => '07',
        'août'      => '08',
        'septembre' => '09',
        'octobre'   => '10',
        'novembre'  => '11',
        'décembre'  => '12',
    ];

    /**
     * Extract a date from a string.
     *
     * @param  string       $str
     * @return string|null
     */
    protected function extractDate($str)
    {
        if ($date = $this->match('#(\d+)(?:er)? (\S+) (\d+)#', $str)) {

            $day   = str_pad($date[1], '2', '0', STR_PAD_LEFT);
            $month = $this->months[$date[2]];

            return $date[3].'-'.$month.'-'.$day;
        }
    }

    /**
     * Object oriented wrapper around preg_match().
     *
     * @param  string  $pattern
     * @param  string  $subject
     * @param  int     $offset
     * @return array
     */
    protected function match($pattern, $subject, $offset = 0)
    {
        $matches = [];

        preg_match($pattern, $subject, $matches, 0, $offset);

        return $matches;
    }

    /**
     * Extended trim utility method to deal with some crazy use cases that can
     * be found on official websites.
     *
     * @param  string  $str
     * @return string
     */
    protected function trim($str)
    {
        $regex = [
            // Replace non-breaking spaces by normal spaces
            '# #'      => ' ',
            // Replace multiple adjacent spaces by a single one
            '#\s{2,}#' => ' ',
        ];

        $str = preg_replace(array_keys($regex), array_values($regex), $str);

        // Quickly trim the string (faster than regex)
        return trim($str);
    }

    /**
     * Recursive extended trim utility method to trim arrays of strings.
     *
     * @param  array  $array
     * @return array
     */
    protected function trimArray(array $array)
    {
        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $value = $this->trimArray($value);
            } elseif (is_string($value)) {
                $value = $this->trim($value);
            }

            $array[$key] = $value;
        }

        return $array;
    }
}
