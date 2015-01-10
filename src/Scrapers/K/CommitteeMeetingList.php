<?php namespace Pandemonium\Methuselah\Scrapers\K;

/**
 * Extract links to current agenda pages
 * of detailed committees meetings.
 *
 * Each of the returned URLs targets a page detailing
 * the meetings of a given committee (or group of
 * committees) that are planned for the week.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeMeetingList extends AbstractScraper
{
    /**
     * Scrape the page of committee agenda lists
     * to find links to agenda pages.
     *
     * @return array
     */
    public function scrape()
    {
        $urls = [];

        foreach ($this->getAgendaAnchors() as $DOMElement) {

            $href = $DOMElement->getAttribute('href');

            if (!$this->matchCommitteeWeek($href)) continue;

            $urls[] = 'http://www.lachambre.be/kvvcr/'.$this->removeHash($href);
        }

        // Remove duplicates and reset indices.
        return array_values(array_unique($urls));
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
     * Remove the hash from a URL.
     *
     * @param  string  $href
     * @return string
     */
    protected function removeHash($href)
    {
        $hashStartPosition = mb_strrpos($href, '#');

        return mb_substr($href, 0, $hashStartPosition);
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
