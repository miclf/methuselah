<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

use Exception;

/**
 * Reorganize the tree of a dossier to an improved structure.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Transformer
{
    /**
     * The source tree that will be transformed.
     *
     * @var array
     */
    protected $source = [];

    /**
     * Transform the given tree.
     *
     * @param  array  $source
     * @return array
     */
    public function transform(array $source)
    {
        $this->source = $source;

        return $source;
    }

    /**
     * Convert a date to an ISO 8601 date format.
     *
     * @param  string  $date
     * @return string
     *
     * @throws \Exception if the date format is unknown.
     */
    protected function dateToIso($date)
    {
        // Date in DD/MM/YYYY format.
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $date, $matches)) {

            return $matches[3].'-'.$matches[2].'-'.$matches[1];
        }

        // Date as a series of numbers, in YYYYMMDD format.
        if (ctype_digit($date) && strlen($date) === 8) {

            return
                substr($date, 0, 4) . '-' .
                substr($date, 4, 2) . '-' .
                substr($date, 6);
        }

        throw new Exception("Unrecognized date format [$date]");
    }
}
