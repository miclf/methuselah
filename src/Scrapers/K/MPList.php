<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Extract data from the list of members of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MPList extends AbstractScraper
{
    /**
     * Scrape a list of members of the Chamber and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        if ($this->wantsSpecificLegislature()) {
            $list = $this->listMPInfo();
        } else {
            $list = $this->listMPAndGroupInfo();
        }

        return $this->trimArray($list);
    }

    /**
     * Get the list of info for all MPs.
     *
     * @return array
     */
    protected function listMPInfo()
    {
        return $this->getRows()->each(function ($row) {

            $mp = $this->getMPInfo($row);

            return $this->sort($mp);
        });
    }

    /**
     * Get the list of info for all MPs, including groups.
     *
     * @return array
     */
    protected function listMPAndGroupInfo()
    {
        return $this->getRows()->each(function ($row) {

            $mp    = $this->getMPInfo($row);
            $group = $this->getGroupInfo($row);

            return $this->sort($mp + $group);
        });
    }

    /**
     * Get all the HTML table rows storing MP data.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getRows()
    {
        return $this->getCrawler()->filter('table[width="100%"] tr');
    }

    /**
     * Check if a specific legislature number has been
     * provided as an option for the scraper.
     *
     * @return bool
     */
    protected function wantsSpecificLegislature()
    {
        return (bool) $this->getOption('legislature_number');
    }

    /**
     * Get the name and identifier of a MP.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function getMPInfo(Crawler $row)
    {
        $anchor = $row->filter('td:first-child a');

        return [
            'surname_given_name' => $anchor->text(),
            'identifier'         => $this->getMPIdentifier($anchor)
        ];
    }

    /**
     * Get the Chamber identifier of a MP.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return string
     */
    protected function getMPIdentifier(Crawler $anchor)
    {
        // Chamber MP identifiers normally consist entirely of digits.
        // But they can also use the capital letter ‘O’, which really
        // looks like a zero but isn’t.
        $pattern = '#key=([\dO]+)#';

        $matches = $this->match($pattern, $anchor->attr('href'));

        return $matches[1];
    }

    /**
     * Get the name and identifier of a political group.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function getGroupInfo(Crawler $row)
    {
        $anchor = $row->filter('td:nth-child(2) a');

        return [
            'political_group'            => $anchor->text(),
            'political_group_identifier' => $this->getGroupIdentifier($anchor)
        ];
    }

    /**
     * Get the Chamber identifier of a political group.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return string
     */
    protected function getGroupIdentifier(Crawler $anchor)
    {
        $pattern = '#namegroup=([^&]+)&#';

        $identifier = $this->match($pattern, $anchor->attr('href'))[1];

        // Prevent ‘é’ characters to be decoded incorrectly.
        $identifier = str_replace('%E9', 'é', $identifier);

        // Group identifiers are URL-encoded, so we need to decode them.
        return urldecode($identifier);
    }

    /**
     * Sort an array by key.
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
     * Return the appropriate parameters for the document provider.
     *
     * This returns an indexed array of two elements. The first is the
     * pattern string and the second is an array of pattern values.
     *
     * @return array
     */
    protected function getProviderArguments()
    {
        $pattern = 'k.mp_list.current';
        $value = [];

        // Set the relevant parameters if a specific legislature is requested.
        if ($number = $this->getOption('legislature_number')) {
            $pattern = 'k.mp_list.legislature';
            $value   = ['legislatureNumber' => $number];
        }

        return [$pattern, $value];
    }
}
