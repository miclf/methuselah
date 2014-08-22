<?php namespace Pandemonium\Methuselah\UrlRepositories;

use Exception;

/**
 * Provide URLs via a JSON file.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class JsonUrlRepository implements UrlRepositoryInterface
{
    /**
     * Source JSON file of the repository.
     *
     * @var string
     */
    protected $sourceFile;

    /**
     * Content of the repository, categorized by parliaments.
     *
     * @var array
     */
    protected $parliaments = [];

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        return $this->loadJson();
    }

    /**
     * Find a URL by key.
     *
     * @param  string        $key
     * @param  string|array  $values
     * @return string
     *
     * @throws \Exception if the key matches zero or more than one URL.
     * @throws \Exception if the value is missing for any required placeholder.
     */
    public function find($key, $values = null)
    {
        $url = array_get($this->parliaments, $key);

        if (!is_string($url) || !$url) {
            throw new Exception("No URL found for key [$key]");
        }

        if ($placeholders = $this->getPlaceholders($url)) {
            return $this->fillPlaceholders($url, $placeholders, $values);
        }

        return $url;
    }

    /**
     * Get the path to the source JSON file.
     *
     * @return string
     */
    public function getSource()
    {
        // Assign a default source file if none has been defined yet.
        if (!isset($this->sourceFile)) {
            return $this->sourceFile = __DIR__.'/data/urls.json';
        }

        return $this->sourceFile;
    }

    /**
     * Set a source JSON file and refill the repository.
     *
     * @param  string  $path
     * @return self
     */
    public function setSource($path)
    {
        $this->sourceFile = $path;

        return $this->loadJson();
    }

    /**
     * Extract the placeholders of a URL pattern.
     *
     * @param  string  $url
     * @return array|false
     */
    protected function getPlaceholders($url)
    {
        if (preg_match_all('#{([A-Za-z]+)}#', $url, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Replace placeholders by values in a given URL pattern.
     *
     * @param  string        $url
     * @param  array         $placeholders
     * @param  string|array  $values
     * @return string
     *
     * @throws \Exception if the value is missing for any given placeholder.
     */
    protected function fillPlaceholders($url, $placeholders, $values)
    {
        $values = (array) $values;

        foreach ($placeholders as $key) {

            if (!isset($values[$key])) {
                throw new Exception("No value provided for placeholder $key");
            }

            $url = str_replace('{'.$key.'}', $values[$key], $url);
        }

        return $url;
    }

    /**
     * Load the content of the source JSON file into the object.
     *
     * @return self
     */
    protected function loadJson()
    {
        $json = json_decode(file_get_contents($this->getSource()), true);

        $this->parliaments = [];

        // We will loop on all assemblies and check for each of them if
        // they have a base URL. If it is the case, we will recursively
        // preprend this URL to all their patterns.
        foreach ($json['parliaments'] as $key => $parliament) {

            if (array_key_exists('baseUrl', $parliament)) {
                $this->parliaments[$key] = $this->prependBaseUrl($parliament);
            } else {
                $this->parliaments[$key] = $parliament;
            }
        }

        return $this;
    }

    /**
     * Recursively prepend a base URL to a set of patterns.
     *
     * @param  array   $patterns
     * @param  string  $baseUrl
     * @return array
     */
    protected function prependBaseUrl($patterns, $baseUrl = null)
    {
        if (!isset($baseUrl)) {
            $baseUrl = array_pull($patterns, 'baseUrl');
        }

        foreach ($patterns as $key => $pattern) {

            // If this patterns has subpatterns, recursively prepend them.
            if (is_array($pattern)) {
                $patterns[$key] = $this->prependBaseUrl($pattern, $baseUrl);
            } else {
                $patterns[$key] = $baseUrl.$pattern;
            }
        }

        return $patterns;
    }
}
