<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Exception;
use Patchwork\Utf8;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the list of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeList extends AbstractScraper
{
    /**
     * The list of committee types, mapped
     * to their French and Dutch names.
     *
     * @var array
     */
    protected $types = [
        // French
        "ENQUÊTES"                      => 'investigation',
        "SPÉCIALES"                     => 'special',
        "TEMPORAIRES"                   => 'temporary',
        "COMITÉS D'AVIS"                => 'advisory',
        "SOUS-COMMISIONS"               => 'subcommittees',
        "GROUPES DE TRAVAIL"            => 'working-group',
        "COMMISSIONS PERMANENTES"       => 'permanent',
        "DÉLÉGATIONS INTERNATIONALES"   => 'international-delegation',

        // Dutch
        "ONDERZOEKSCOMMISSIES"          => 'investigation',
        "BIJZONDERE"                    => 'special',
        "TIJDELIJKE"                    => 'temporary',
        "ADVIESCOMITÉS"                 => 'advisory',
        "SUBCOMMISSIES"                 => 'subcommittees',
        "WERKGROEPEN"                   => 'working-group',
        "VASTE COMMISSIES"              => 'permanent',
        "INTERNATIONALE AFGEVAARDIGDEN" => 'international-delegation',
    ];

    /**
     * The list of committees.
     *
     * @var array
     */
    protected $committees = [];

    /**
     * Scrape the pages of lists of committees
     * and extract their information.
     *
     * @return array
     *
     * @throws \Exception if a committee type cannot be determined.
     */
    public function scrape()
    {
        foreach (['fr', 'nl'] as $lang) {
            $this->scrapeCommitteeList($lang);
        }

        return $this->getCommittees();
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
        return ['k.committee_list', ['lang' => $lang]];
    }

    /**
     * Scrape a page of list of committees
     * and extract its information.
     *
     * @param  string  $lang
     * @return array
     *
     * @throws \Exception if a committee type cannot be determined.
     */
    protected function scrapeCommitteeList($lang)
    {
        $type = null;

        $this->getNodes($lang)->each(function ($node) use (&$type, $lang) {

            // If the current node introduces a committee type, we
            // store it and, until we encounter a new one, all the
            // next committees will be of that type.
            if ($str = $this->getCommitteeType($node)) {

                $type = $str;

                return;
            }

            // Get the identifier of the current committee.
            $matches = $this->match('#com=(\d+)#', $node->attr('href'));
            $identifier = $matches[1];

            // Determine the committee name in the current language.
            $name = Utf8::strtolower(trim($node->text()));

            // If this committee has already been added to the list in
            // a previous iteration, then we only need to add the name
            // in the current lang, because the rest of the data is
            // already there. Otherwise, we set everything.
            if (isset($this->committees[$identifier])) {

                $this->committees[$identifier]['name_'.$lang] = $name;

            } else {

                $this->committees[$identifier] = [
                    'identifier'  => $identifier,
                    'type'        => $type,
                    'name_'.$lang => $name,
                ];
            }
        });
    }

    /**
     * Get a DOM crawler prefilled with only the
     * node set relevant for the scraper.
     *
     * @return \Pandemonium\Methuselah\Crawler\Crawler
     */
    public function getNodes($lang)
    {
        list($pattern, $values) = $this->getProviderArguments($lang);

        $document = $this->getDocument($pattern, $values);

        return $this->getCrawler($document)->filter('#content h4, #content a');
    }

    /**
     * Get a committee type from a node.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return string|null
     *
     * @throws \Exception if the type cannot be determined.
     */
    protected function getCommitteeType(Crawler $node)
    {
        if ($node->getNode(0)->tagName === 'h4') {

            // The tag contains the French or Dutch name of the type. We
            // loop on a map to find the associated ‘normalized’ name.
            $str = trim($node->text());

            foreach ($this->types as $needle => $type) {
                if ($str === $needle) return $type;
            }

            throw new Exception('Cannot determine type of committee');
        }
    }

    /**
     * Return the list of committees as an indexed
     * array of associative arrays.
     *
     * @return array
     */
    protected function getCommittees()
    {
        $list = [];

        foreach ($this->committees as $committee) {
            $list[] = $committee;
        }

        return $list;
    }
}
