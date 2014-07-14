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
     * Find a URL pattern by key.
     *
     * @param  string  $key
     * @return string
     *
     * @throws \Exception if the key matches zero or more than one URL.
     */
    public function find($key)
    {
        $pattern = array_get($this->parliaments, $key);

        if (!is_string($pattern) || !$pattern) {
            throw new Exception("No URL found for key [$key]");
        }

        return $pattern;
    }

    /**
     * Get the path to the source JSON file.
     *
     * @return string
     */
    public function getSourceFile()
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
    public function setSourceFile($path)
    {
        $this->sourceFile = $path;

        return $this->loadJson();
    }

    /**
     * Load the content of the source JSON file into the object.
     *
     * @return self
     */
    protected function loadJson()
    {
        $json = json_decode(file_get_contents($this->getSourceFile()), true);

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
