<?php namespace Pandemonium\Methuselah\Scrapers;

/**
 * Common interface that all scrapers have to implement.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
interface ScraperInterface
{
    /**
     * Scrape data from a web page and return it.
     *
     * @return array
     */
    public function scrape();
}
