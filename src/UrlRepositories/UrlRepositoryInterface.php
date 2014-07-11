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
     */
    public function find($key);
}
