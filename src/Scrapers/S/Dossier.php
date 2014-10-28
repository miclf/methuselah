<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the pages of dossiers of the Senate.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Dossier extends AbstractScraper
{
    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * Scrape the page of a dossier and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        $this->crawler = $this->getCrawler();

        $dossier = [];

        $dossier['meta'] = $this->getMetadata();

        $dossier['keywords'] = [
            'fr' => $this->extractKeywords()
        ];

        $dossier['documents'] = $this->extractDocuments();

        return $dossier;
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
    protected function getProviderArguments($lang = 'fr')
    {
        // Split the dossier identifier to get the legislature
        // number and the number of the dossier.
        $matches = $this->match('#(\d+)S(\d+)#', $this->getOption('identifier'));

        $values = [
            'legislatureNumber' => $matches[1],
            'dossierNumber'     => $matches[2],
            'lang'              => $this->getOption('lang', 'fr'),
        ];

        return ['s.dossier', $values];
    }

    /**
     * Get the metadata of the dossier.
     *
     * @return array
     */
    protected function getMetadata()
    {
        // Get a crawler for the <tr> elements of the first table.
        $rows = $this->crawler->filter('table:first-child tr');

        // The first row stores the full number of the dossier.
        $data = $this->extractLegislatureAndDossierNumber($rows->first());

        // The title of the dossier is located in the second row of the table.
        $data['title'] = [
            'fr' => trim($rows->eq(1)->text())
        ];

        // The third table row may contain a list of authors.
        $data['authors'] = $this->extractAuthors($rows->last());

        return $data;
    }

    /**
     * Get the legislature number and the number of the dossier.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function extractLegislatureAndDossierNumber(Crawler $row)
    {
        $identifier = (string) $row->children()->first();

        $matches = $this->match('#(\d+)-(\d+)#', $identifier);

        return [
            'legislature' => $matches[1],
            'number'      => $matches[2]
        ];
    }

    /**
     * Get the list of authors.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array|null
     */
    protected function extractAuthors(Crawler $row)
    {
        $anchors = $row->filter('a');

        if (!count($anchors)) return null;

        // We will loop on all the links and extract their info.
        return $anchors->each(function ($anchor) {

            $matches = $this->match('#ID=(\d+)#', $anchor->attr('href'));

            return [
                'identifier'         => $matches[1],
                'given_name_surname' => trim($anchor->text())
            ];
        });
    }

    /**
     * Get the list of keywords.
     *
     * @return array
     */
    protected function extractKeywords()
    {
        // All the keywords of the dossier are contained in a single
        // table cell and separated by <br> elements. We explode
        // this string and then clean the array that we got.
        $cell = $this->crawler->filter('table:nth-of-type(2) td');

        if (!count($cell)) return null;

        $keywords = explode('<br>', $cell->html());

        // Trim values and remove empty ones.
        return array_filter(array_map('trim', $keywords));
    }

    /**
     * Get the list of documents.
     *
     * @return array|null
     */
    protected function extractDocuments()
    {
        // The third table of the page contains the list of documents.
        // We grab all its rows except the first one, which stores
        // the names of the columns. We then simply make a loop.
        $rows = $this->crawler->filter('table:nth-of-type(3) tr:nth-child(n+2)');

        return $rows->each(function ($row) {

            $cells = $row->children();

            return [
                'number' => trim($cells->eq(0)->text()),
                'type'   => trim($cells->eq(1)->text()),
                'date'   => $this->parseDate($cells->eq(2)->text()),
                'links'  => $this->parseDocumentLinks($cells->eq(0)),
            ];
        });
    }

    /**
     * Find document links and extract info from them.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function parseDocumentLinks(Crawler $node)
    {
        $data = $found = [];

        // We will loop on all the links and extract their info.
        $node->filter('a')->each(function ($anchor) use (&$data, &$found) {

            // We look for a document format, normally specified inside
            // parentheses in the title attribute of each anchor.
            if ($matches = $this->match('#\((.+)\)#', $anchor->attr('title'))) {

                $format = strtolower($matches[1]);
                $url    = 'http://senate.be'.$anchor->attr('href');

                // The website of the Senate sometimes shows multiple links to
                // the exact same document. We then need to keep track of the
                // ones we already added so that we don’t store duplicates.
                if (in_array($url, $found)) {
                    return;
                }

                $found[] = $url;

                $data[] = compact('format', 'url');
            }
        });

        return $data;
    }

    /**
     * Convert a date to ISO 8601.
     *
     * @param  string  $date
     * @return string
     */
    protected function parseDate($date)
    {
        $parts = explode('/', trim($date));

        return
            $parts[2].'-'.
            str_pad($parts[1], 2, '0', STR_PAD_LEFT).'-'.
            str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    }
}
