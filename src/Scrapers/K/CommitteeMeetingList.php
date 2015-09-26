<?php namespace Pandemonium\Methuselah\Scrapers\K;

/**
 * Extract identifiers of current agenda pages
 * of detailed committees meetings.
 *
 * Each of the returned IDs targets a page detailing
 * the meetings of a given committee (or group of
 * committees) that are planned for the week.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeMeetingList extends AbstractScraper
{
    /**
     * Scrape the page of committee agenda lists
     * to find identifiers of agenda pages.
     *
     * @return array
     */
    public function scrape()
    {
        $identifiers = [];

        foreach ($this->getAgendaAnchors() as $DOMElement) {

            $href = $DOMElement->getAttribute('href');

            if (!$matches = $this->matchCommitteeWeek($href)) continue;

            $identifiers[] = $matches[1];
        }

        // Remove duplicates and reset indices.
        return array_values(array_unique($identifiers));
    }

    /**
     * Return the HTML anchors in the main
     * content area of the document.
     *
     * @return \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected function getAgendaAnchors()
    {
        return $this->getCrawler()->filter('#content a');
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
        if ($crawler = $this->getOption('crawler')) {
            return $crawler;
        } elseif ($url = $this->getOption('url')) {
            return parent::getCrawlerFrom($url);
        }

        return parent::getCrawler();
    }

    /**
     * Check if a given URL targets the
     * agenda page of a committee week.
     *
     * @param  string  $href
     * @return array
     */
    protected function matchCommitteeWeek($href)
    {
        // This pattern captures the identifier of the committee week.
        $pattern = '#pat=PROD-commissions&type=full&com=(\d+-\d+_\d+)#';

        return $this->match($pattern, $href);
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
        return ['k.agenda_list.committee', []];
    }
}
