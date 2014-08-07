<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Pandemonium\Methuselah\Scrapers\AbstractScraper;

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
        $list = $value = [];

        $pattern = 's.mp_list.current';

        $legislatureNumber = array_get($options, 'legislature_number');

        // Set the relevant parameters if a specific legislature is requested.
        if (!is_null($legislatureNumber)) {
            $pattern = 's.mp_list.legislature';
            $value   = compact('legislatureNumber');
        }

        $html = $this->documentProvider->get($pattern, $value);

        $crawler = $this->newCrawler();

        // The page is encoded in ISO-8859-1, so we explicitly specify
        // this in order to avoid any character encoding issue.
        $crawler->addHtmlContent($html, 'ISO-8859-1');

        // Get the <table> storing the list of MPs and loop on its rows.
        // The correct table is the second one of the page
        $rows = $crawler->filter('table:nth-of-type(2)>tr');

        $rows->each(function ($row, $i) use (&$list) {

            // Get the first <td> element of this table row.
            $cell = $row->children()->eq(0);

            // This will store the data of the current MP.
            $mp = [];


            // The cell contains an anchor linking to the page of the MP.
            // We will extract her or his surname and given name and the
            // Senate ID from this anchor.
            $anchor = $cell->filter('tr:first-child a');

            // If the cell contains no link, we probably reached empty rows
            // at the end of the table. We skip the row.
            if (!count($anchor)) return;

            preg_match('#ID=([\d]+)#', $anchor->attr('href'), $matches);
            $mp['identifier'] = $matches[1];

            $mp['given_name_surname'] = $anchor->text();

            // Add the data of the current MP to the list.
            $list[] = $mp;

        });

        return $this->trimArray($list);
    }
}
