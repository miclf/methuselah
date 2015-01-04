<?php namespace Pandemonium\Methuselah\Scrapers\S;

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
            $mp = [];


            // The table row contains an anchor linking to the page of
            // the MP. We will extract her or his surname and given
            // name and the Senate ID from this anchor.
            $anchor = $row->filter('a');

            // If the row contains no link, we probably reached empty
            // rows at the end of the table. We skip the row.
            if (!count($anchor)) return;

            $matches = $this->match('#ID=([\d]+)#', $anchor->attr('href'));
            $mp['identifier'] = $matches[1];

            $mp['surname_given_name'] = $anchor->text();

            // Add the data of the current MP to the list.
            $list[] = $mp;

        });

        return $list;
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
