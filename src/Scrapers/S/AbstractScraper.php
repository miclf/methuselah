<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Pandemonium\Methuselah\Scrapers\AbstractScraper as BaseAbstractScraper;

/**
 * Abstract scraper class for the site of the Senate.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractScraper extends BaseAbstractScraper
{
    /**
     * Character set of the scraped documents.
     *
     * @var string
     */
    protected $charset = 'ISO-8859-1';
}
