<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Extract data from the list of senators.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MPList extends AbstractScraper
{
    /**
     * Scrape a list of senators and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        $list = [];

        $crawler = $this->getCrawler();

        // Get the <table> storing the list of MPs and loop on its rows.
        // The correct table is the second one of the page
        $rows = $crawler->filter('table:nth-of-type(2)>tr');

        $rows->each(function ($row, $i) use (&$list) {

            // This will store the data of the current MP.
            $list[] = $this->getMPInfo($row);

        });

        // Remove potential null values from the list.
        $list = array_filter($list);

        return $list;
    }

    /**
     * Get the name and identifier of a MP.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array|null
     */
    protected function getMPInfo(Crawler $row)
    {
        $anchor = $row->filter('a');

        // We skip the row if it does not contain any anchor
        // nor, as a result, any info about a MP.
        if (!count($anchor)) return;

        return [
            'identifier'         => $this->getMPIdentifier($anchor),
            'surname_given_name' => $anchor->text()
        ];
    }

    /**
     * Get the Senate identifier of a MP.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return string
     */
    protected function getMPIdentifier(Crawler $anchor)
    {
        $pattern = '#ID=([\d]+)#';

        $matches = $this->match($pattern, $anchor->attr('href'));

        return $matches[1];
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
        $pattern = 's.mp_list.current';
        $value = [];

        // Set the relevant parameters if a specific legislature is requested.
        if ($number = $this->getOption('legislature_number')) {
            $pattern = 's.mp_list.legislature';
            $value   = ['legislatureNumber' => $number];
        }

        return [$pattern, $value];
    }
}
