<?php namespace Pandemonium\Methuselah\Scrapers\S;

/**
 * Extract identifiers of current agenda pages
 * of detailed plenary meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class PlenaryMeetingList extends AbstractScraper
{
    /**
     * Scrape the page of plenary agenda lists
     * to find identifiers of agenda pages.
     *
     * @return array
     */
    public function scrape()
    {
        $identifiers = [];

        foreach ($this->getAgendaAnchors() as $DOMElement) {

            $href = $DOMElement->attr('href');

            if (!$matches = $this->matchValidAgendaLink($href)) continue;

            $identifiers[] = $matches[1];
        }

        // Remove duplicates and reset indices.
        $identifiers = array_values(array_unique($identifiers));

        return $this->sort($identifiers);
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
        // This pattern captures the identifier of the plenary week.
        return $this->match('#ID=(\d+)&TYP=plenag#', $href);
    }

    /**
     * Sort an array by value.
     *
     * @param  array  $array
     * @return array
     */
    protected function sort(array $array)
    {
        sort($array);

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

        return ['s.agenda_list.plenary_weeks', $values];
    }
}
