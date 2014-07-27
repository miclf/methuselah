<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Symfony\Component\DomCrawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;

/**
 * Extract data from the list of senators.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MPList
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

    /**
     * Scrape a list of senators and extract its information.
     *
     * @param  int    $legislatureNumber
     * @return array
     */
    public function scrape($legislatureNumber = null)
    {
        $list = $value = [];

        $pattern = 's.mp_list.current';

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

            $mp['given_name_surname'] = $this->trim($anchor->text());

            // Add the data of the current MP to the list.
            $list[] = $mp;

        });

        return $list;
    }

    /**
     * Get the document provider of the scraper.
     *
     * @return \Pandemonium\Methuselah\DocumentProvider
     */
    public function getDocumentProvider()
    {
        return $this->documentProvider;
    }

    /**
     * Create a new DomCrawler.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function newCrawler()
    {
        return new Crawler;
    }

    /**
     * Extended trim utility method to deal with some of the endless suprises of
     * the website of the Chamber.
     *
     * @param  string  $str
     * @return string
     */
    protected function trim($str)
    {
        $regex = [
            // Replace non-breaking spaces by normal spaces
            '# #'      => ' ',
            // Replace multiple adjacent spaces by a single one
            '#\s{2,}#' => ' ',
        ];

        $str = preg_replace(array_keys($regex), array_values($regex), $str);

        // Quickly trim the string before returning it (faster than regex)
        return trim($str);
    }
}
