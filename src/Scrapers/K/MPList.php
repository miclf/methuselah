<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Symfony\Component\DomCrawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;

/**
 * Extract data from the list of members of the Chamber.
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
     * Scrape the list of members of the Chamber and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        $list = [];

        $html = $this->documentProvider->get('k.mp_list.current');

        $crawler = $this->newCrawler();

        // The page is encoded in ISO-8859-1, so we explicitly specify
        // this in order to avoid any character encoding issue.
        $crawler->addHtmlContent($html, 'ISO-8859-1');

        // Get the <table> storing the list of MPs and loop on its rows.
        $rows = $crawler->filter('table[width="100%"] tr');

        $rows->each(function ($row, $i) use (&$list) {

            // Get the <td> elements of this table row.
            $cells = $row->children();

            // This will store the data of the current MP.
            $mp = [];


            // Process the first <td> cell.
            // It contains an achor linking to the page of the MP. We will
            // extract the surname and given name of the MP and its Chamber ID
            // from this anchor.
            $anchor = $cells->eq(0)->filter('a');

            $mp['surname_given_name'] = $this->trim($anchor->text());

            // Chamber MP identifiers normally consist entirely of digits. But
            // they can also use the capital letter ‘O’, which really looks like
            // a zero but isn’t.
            preg_match('#key=([\dO]+)#', $anchor->attr('href'), $matches);
            $mp['identifier'] = $matches[1];


            // Second <td>.
            // This one has an anchor containing the name and the Chamber ID of
            // the ‘political group’. This group may be an official group or a
            // party name.
            $anchor = $cells->eq(1)->filter('a');

            $mp['political_group'] = $this->trim($anchor->text());

            // Group identifiers are URL-encoded, so we need to decode them.
            preg_match('#namegroup=([^&]+)&#', $anchor->attr('href'), $matches);
            $mp['political_group_identifier'] = urldecode($matches[1]);


            // Third <td> cell.
            // This one MAY contain the official e-mail address of the MP.
            $mp['e-mail'] = $this->trim($cells->eq(2)->text()) ?: null;


            // Process the last <td>.
            // It MAY contain a link to a website chosen by the MP. It could be
            // a personal site, the site of the party, etc.
            $mp['website'] = null;

            if (count($anchor = $cells->eq(3)->filter('a'))) {
                $mp['website'] = $anchor->attr('href');
            }


            // All the cells of the row have been processed. We can
            // now add the data of the current MP to the list.
            $list[] = $mp;

        });

        return $list;
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
            '# #' => ' ',
            // Replace multiple adjacent spaces by a single one
            '#\s{2,}#' => ' ',
        ];

        $str = preg_replace(array_keys($regex), array_values($regex), $str);

        // Quickly trim the string before returning it (faster than regex)
        return trim($str);
    }
}
