<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

use Exception;

/**
 * Reorganize the default tree of a dossier to an improved structure.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Transformer
{
    // Include mapping data.
    use MappingTrait;

    // Include dictionaries.
    use DictionariesTrait;

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
     * Look for an entry in a key-value dictionary.
     *
     * @param  string        $needle      The entry to look for
     * @param  string|array  $dictionary  A dictionary array or a property name
     * @return mixed
     *
     * @throws \Exception if the given key cannot be found in the dictionary.
     */
    protected function findInDictionary($needle, $dictionary)
    {
        if (is_string($dictionary)) {
            $dictionary = $this->$dictionary;
        }

        foreach ($dictionary as $key => $value) {
            if ($key === $needle) return $value;
        }

        throw new Exception("Cannot find key [$needle] in dictionary.");
    }

    /**
     * Convert a date to the YYYY-MM-DD ISO 8601 format.
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
