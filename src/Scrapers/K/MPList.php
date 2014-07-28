<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Symfony\Component\DomCrawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;
use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the list of members of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MPList extends AbstractScraper
{
    /**
     * Constructor.
     *
     * @param  \Pandemonium\Methuselah\DocumentProvider  $documentProvider
     * @return self
     */
    public function __construct(DocumentProvider $documentProvider)
    {
        parent::__construct($documentProvider);

        // The website of the Chamber does not respect RFC 1738 nor RFC 3986. In
        // order to work with it, query strings must not be encoded. We then
        // disable the encoding operation that is executed by default.
        $this->documentProvider->setQueryEncoding(false);
    }

    /**
     * Scrape a list of members of the Chamber and extract its information.
     *
     * @param  array  $options
     * @return array
     */
    public function scrape(array $options = null)
    {
        $list = $value = [];

        $pattern = 'k.mp_list.current';

        $legislatureNumber = array_get($options, 'legislature_number');

        // Set the relevant parameters if a specific legislature is requested.
        if (!is_null($legislatureNumber)) {
            $pattern = 'k.mp_list.legislature';
            $value   = compact('legislatureNumber');
        }

        $html = $this->documentProvider->get($pattern, $value);

        $crawler = $this->newCrawler();

        // The page is encoded in ISO-8859-1, so we explicitly specify
        // this in order to avoid any character encoding issue.
        $crawler->addHtmlContent($html, 'ISO-8859-1');

        // Get the <table> storing the list of MPs and loop on its rows.
        $rows = $crawler->filter('table[width="100%"] tr');

        $rows->each(function ($row, $i) use (&$list, $legislatureNumber) {

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


            // In the lists of previous legislatures, the following cells are
            // empty and the information is not available anymore. We then
            // stop the scraping of the row here and go to the next one.
            if ($legislatureNumber) {

                // Store the scraped data and stop the current iteration.
                $list[] = $mp;

                return;
            }

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
}
