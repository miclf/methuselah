<?php namespace Pandemonium\Methuselah\Scrapers;

/**
 * Helper methods for scraping operations.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
trait ScrapingHelpersTrait
{
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
