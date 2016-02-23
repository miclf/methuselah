<?php namespace Pandemonium\Methuselah\Scrapers\S;

/**
 * Extract identifiers of current agenda
 * pages of detailed meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class AbstractMeetingListScraper extends AbstractScraper
{
    /**
     * The pattern to use to validate a link and extract data from it.
     *
     * @var string
     */
    protected $linkPattern;

    /**
     * The key to request from the URL repository.
     *
     * @var string
     */
    protected $urlRepositoryKey;

    /**
     * Scrape the page of agenda lists to find dates
     * and identifiers of agenda meetings.
     *
     * @return array
     */
    public function scrape()
    {
        $meetings = [];

        foreach ($this->getAgendaAnchors() as $DOMElement) {

            $href = $DOMElement->attr('href');

            if (!$matches = $this->matchValidAgendaLink($href)) continue;

            $meetings[$matches[2]] = [
                'date' => $this->extractDate($matches[1]),
                'identifier' => $matches[2],
            ];
        }

        return $this->sort($meetings);
    }

    /**
     * Return the HTML anchors in the main
     * content area of the document.
     *
     * @return \QueryPath\DOMQuery
     */
    protected function getAgendaAnchors()
    {
        return $this->getCrawler()->find('a');
    }

    /**
     * Get a DOM crawler prefilled with the whole document.
     *
     * @param  string  $document
     * @param  string  $charset
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler($document = null, $charset = null)
    {
        if ($url = $this->getOption('url')) {
            return parent::getCrawlerFrom($url);
        }

        return parent::getCrawler();
    }

    /**
     * Check if a given URL targets a valid agenda page.
     *
     * @param  string  $href
     * @return array
     */
    protected function matchValidAgendaLink($href)
    {
        // This pattern captures the identifier of the week.
        return $this->match($this->linkPattern, $href);
    }

    /**
     * Extract a date from a string.
     *
     * @param  string       $str
     * @return string|null
     */
    protected function extractDate($str)
    {
        $date = explode('/', $str);

        // Date is in the `MM/DD/YYYY` format.
        // We convert it to ISO 8601.
        return $date[2].'-'.$date[0].'-'.$date[1];
    }

    /**
     * Sort an array by value.
     *
     * @param  array  $array
     * @return array
     */
    protected function sort(array $array)
    {
        ksort($array);

        return $array;
    }

    /**
     * Set up a DOM crawler and fill it with the given document.
     *
     * @param  string  $document
     * @param  string  $charset
     * @return \QueryPath\DOMQuery
     */
    protected function buildCrawler($document, $charset = null)
    {
        if (is_null($charset)) {
            $charset = $this->charset;
        }

        // Convert the content of the document to UTF-8 in case it
        // uses another encoding (I’m looking at you, ISO-8859-1).
        if ($charset !== 'UTF-8') {
            $document = mb_convert_encoding($document, 'UTF-8', $charset);
        }

        // Create an HTML5-aware crawler.
        return \html5qp($document);
    }

    /**
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @param  string  $lang
     * @return array
     */
    protected function getProviderArguments()
    {
        $values = [
            'lang'     => 'fr',
            'weekType' => $this->getOption('week_type', 'week'),
        ];

        return [$this->urlRepositoryKey, $values];
    }
}
