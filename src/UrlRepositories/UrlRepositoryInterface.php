<?php namespace Pandemonium\Methuselah\UrlRepositories;

/**
 * URL repository interface.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
interface UrlRepositoryInterface
{
    /**
     * Find a URL pattern by key.
     *
     * @param  string  $key
     * @return string
     *
     * @throws \Exception if the key matches zero or more than one URL.
     */
    public function find($key);
}
