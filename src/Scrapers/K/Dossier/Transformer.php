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
     * The transformed tree.
     *
     * @var array
     */
    protected $tree = [];

    /**
     * Transform the given tree.
     *
     * @param  array  $source
     * @return array
     *
     * @throws \Exception if a key cannot be found in a dictionary.
     * @throws \Exception if trying to call a nonexisting modifier.
     */
    public function transform(array $source)
    {
        $this->source = $source;

        foreach ($this->mapping as $key => $mappings) {
            $this->tree += $this->map($key, $mappings);
        }

        return $this->tree;
    }

    /**
     * Apply a series of mappings.
     *
     * @param  string  $rootNode  Path to the root of the mappings
     * @param  array   $mappings  List of mappings to apply
     * @return array
     *
     * @throws \Exception if a key cannot be found in a dictionary.
     * @throws \Exception if trying to call a nonexisting modifier.
     */
    protected function map($rootNode, array $mappings)
    {
        $data = [];

        foreach ($mappings as $sourceKey => $parameters) {

            $parameters = $this->normalizeParameters($rootNode, $parameters);

            $value = $this->getMappingValue($sourceKey, $parameters);

            array_set($data, $parameters['destination'], $value);
        }

        return $data;
    }

    /**
     * Convert mapping parameters to a normalized array.
     *
     * @param  string        $rootNode
     * @param  array|string  $parameters
     * @return array
     */
    protected function normalizeParameters($rootNode, $parameters)
    {
        // If the argument consists of a simple string, it
        // references the destination path of the mapping.
        if (is_string($parameters)) {
            $parameters = ['destination' => $parameters];
        }

        $parameters['destination'] = $this->compileDestination(
            $rootNode,
            $parameters['destination']
        );

        return $parameters;
    }

    /**
     * Build the destination path of a mapping.
     *
     * @param  string  $root
     * @param  string  $path
     * @return string
     */
    protected function compileDestination($root, $path)
    {
        return $root ? "{$root}.{$path}" : $path;
    }

    /**
     * Get a node value from the source tree.
     *
     * @param  string  $path
     * @param  array   $parameters
     * @return mixed
     */
    protected function getMappingValue($path, array $parameters)
    {
        $value = array_get($this->source, $path);

        return $this->processValue($value, $parameters);
    }

    /**
     * Process a mapping value according to its related parameters.
     *
     * @param  mixed  $value
     * @param  array  $parameters
     * @return mixed
     */
    protected function processValue($value, array $parameters)
    {
        // Does the value needs to be modified in some way?
        if (isset($parameters['modifier'])) {
            $value = $this->callModifier($parameters['modifier'], $value);
        }

        // Does the value needs to be replaced by a normalized constant?
        if (isset($parameters['dictionary'])) {
            $value = $this->findInDictionary($value, $parameters['dictionary']);
        }

        return $value;
    }

    /**
     * Call a modifier on a value.
     *
     * @param  string  $modifier  Name of the modifier method
     * @param  mixed   $value     The value to modify
     * @return mixed
     *
     * @throws \Exception if the specified modifier does not exist.
     */
    protected function callModifier($modifier, $value)
    {
        if (method_exists($this, $modifier)) {
            return $this->$modifier($value);
        }

        throw new Exception("Modifier [$modifier] does not exist");
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
            if ((string) $key === $needle) return $value;
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
