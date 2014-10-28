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
}
