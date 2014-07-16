<?php namespace Pandemonium\Methuselah\UrlRepositories;

/**
 * URL repository interface.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
interface UrlRepositoryInterface
{
    /**
     * Find a URL by key.
     *
     * @param  string        $key
     * @param  string|array  $values
     * @return string
     *
     * @throws \Exception if the key matches zero or more than one URL.
     */
    public function find($key, $values = null);
}
