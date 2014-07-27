<?php namespace Pandemonium\Methuselah\Scrapers;

use Symfony\Component\DomCrawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;

/**
 * Base class for all scrapers.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractScraper
{
    /**
     * A document provider.
     *
     * @var \Pandemonium\Methuselah\DocumentProvider
     */
    protected $documentProvider;

    /**
     * Constructor.
     *
     * @param  \Pandemonium\Methuselah\DocumentProvider  $documentProvider
     * @return self
     */
    public function __construct(DocumentProvider $documentProvider)
    {
        $this->documentProvider = $documentProvider;
    }
}
