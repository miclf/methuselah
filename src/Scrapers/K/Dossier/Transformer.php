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
     * @param  array  $mappings    List of mappings to apply
     * @param  array  $dataSource  Optional subset of data to use as data source
     * @return array
     *
     * @throws \Exception if a key cannot be found in a dictionary.
     * @throws \Exception if trying to call a nonexisting modifier.
     */
    protected function map(array $mappings, array $dataSource = null)
    {
        $data = [];

        foreach ($mappings as $sourceKey => $parameters) {

            $parameters = $this->normalizeParameters($parameters);

            $value = $this->getMappingValue($dataSource, $sourceKey, $parameters);

            array_set($data, $parameters['destination'], $value);
        }

        return $data;
    }

    /**
     * Apply mappings for the documents of the dossier.
     *
     * @return array
     */
    protected function mapDocuments()
    {
        $data = array_merge($this->mapMainDocuments(), $this->mapSubdocuments());

        return ['documents' => $data];
    }

    /**
     * Apply mappings for the main documents of the dossier.
     *
     * @return array
     */
    protected function mapMainDocuments()
    {
        $data = [];

        $documents = $this->filterSource('chambre-etou-senat.document-principal');

        foreach ($documents as $i => $document) {
            $data[] = $this->map($this->mapping['main_document'], $document);
        }

        return $data;
    }

    /**
     * Apply mappings for the subdocuments of the dossier.
     *
     * @return array
     */
    protected function mapSubdocuments()
    {
        $data = [];

        $documents = $this->filterSource('chambre-etou-senat.document-principal.0.sous-documents.documents-suivants');

        foreach ($documents as $i => $document) {
            $data[] = $this->map($this->mapping['subdocument'], $document);
        }

        return $data;
    }

    /**
     * Convert mapping parameters to a normalized array.
     *
     * @param  array|string  $parameters
     * @return array
     */
    protected function normalizeParameters($parameters)
    {
        // If the argument consists of a simple string, it
        // references the destination path of the mapping.
        if (is_string($parameters)) {
            $parameters = ['destination' => $parameters];
        }

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
     * Return a subset of the source’s data using ‘dot notation’.
     *
     * This allows to use the key ‘foo.bar.baz’ to get the value of
     * $this->source['foo']['bar']['baz'], thus making it easy to
     * read data that is nested deep in the source array.
     *
     * @param  string  $path     ‘path’ to the target subset of data
     * @param  mixed   $default  Default value to return if nothing is found
     * @return mixed
     */
    protected function filterSource($path, $default = null)
    {
        return array_get($this->source, $path, $default);
    }

    /**
     * Get a node value from the source tree.
     *
     * @param  array   $dataSource
     * @param  string  $path
     * @param  array   $parameters
     * @return mixed
     */
    protected function getMappingValue(array $dataSource = null, $path, array $parameters = [])
    {
        if (!$dataSource) {
            $dataSource = $this->source;
        }

        $value = array_get($dataSource, $path);

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
            return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6);
        }

        throw new Exception("Unrecognized date format [$date]");
    }

    /**
     * Prepend the number of a dossier to a document number.
     *
     * @param  string  $documentNumber
     * @return string
     */
    protected function prependDossierNumber($documentNumber)
    {
        $dossierNumber = array_get($this->tree, 'meta.number');

        return $dossierNumber.$documentNumber;
    }
}
