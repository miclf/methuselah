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
        // Get the table rows storing the info on MPs and loop on them.
        $list = $this->getRows()->each(function ($row, $i) {

            // Get the <td> elements of this table row.
            $cells = $row->children();


            // Process the first <td> cell.
            // It contains an achor linking to the page of the MP. We
            // will extract the surname and given name of the MP and
            // its Chamber ID from this anchor.
            $mp = $this->getMPInfo($cells->eq(0));


            // In the lists of previous legislatures, the following cells are
            // empty and the information is not available anymore. We then
            // stop the scraping of the row here and go to the next one.
            if ($this->wantsSpecificLegislature()) {

                // Store the scraped data and stop the current iteration.
                ksort($mp);

                return $mp;
            }

            // Second table cell.
            // This one has an anchor containing the name and the
            // Chamber ID of the ‘political group’. This group
            // may be an official group or a party name.
            $group = $this->getGroupInfo($cells->eq(1));


            // All the needed cells of the row have been processed. We can
            // now add the data of the current MP to the list.
            $data = $mp + $group;

            ksort($data);

            return $data;
        });

        return $this->trimArray($list);
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
     * @param  \Symfony\Component\DomCrawler\Crawler  $cell
     * @return array
     */
    protected function getMPInfo(Crawler $cell)
    {
        $anchor = $cell->filter('a');

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
     * @param  \Symfony\Component\DomCrawler\Crawler  $cell
     * @return array
     */
    protected function getGroupInfo(Crawler $cell)
    {
        $anchor = $cell->filter('a');

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

        $matches = $this->match($pattern, $anchor->attr('href'));

        // Group identifiers are URL-encoded, so we need to decode them.
        return urldecode($matches[1]);
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
