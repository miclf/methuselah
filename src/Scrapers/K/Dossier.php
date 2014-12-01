<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Pandemonium\Methuselah\Scrapers\K\Dossier\HtmlToXmlConverter;
use Pandemonium\Methuselah\Scrapers\K\Dossier\XmlToArrayConverter;

/**
 * Extract data from the pages of dossiers of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Dossier extends AbstractScraper
{
    /**
     * Scrape the page of a dossier of the Chamber and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        return $this->getDossierAsTree();
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
        // Split the dossier identifier (pattern: 00K0000) to get the
        // legislature number and the number of the dossier.
        $matches = $this->match('#(\d+)K(\d+)#', $this->getOption('identifier'));

        $values = [
            'legislatureNumber' => $matches[1],
            'dossierNumber'     => $matches[2],
            'lang'              => $this->getOption('lang', 'fr'),
        ];

        return ['k.dossier', $values];
    }

    /**
     * Get the data of the dossier as a tree of PHP arrays.
     *
     * @return array
     */
    protected function getDossierAsTree()
    {
        $htmlSource = $this->getCrawler()->html();

        $xml = (new HtmlToXmlConverter)->convert($htmlSource);

        return (new XmlToArrayConverter)->convert($xml);

    }
}
