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
        "SPÉCIALES"               => 'special',
        "COMITÉS D'AVIS"          => 'advisory',
        "COMMISSIONS PERMANENTES" => 'permanent',

        // Dutch
        "BIJZONDERE"              => 'special',
        "ADVIESCOMITÉS"           => 'advisory',
        "VASTE COMMISSIES"        => 'permanent',
    ];

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
        $list = [];
        $type = null;

        foreach (['fr', 'nl'] as $lang) {

            list($pattern, $values) = $this->getProviderArguments($lang);

            $document = $this->getDocument($pattern, $values);

            // Get all the nodes except the first <b> tag, which stores the role.
            $nodes = $this->getCrawler($document)->filter('#content h4, #content a');

            $nodes->each(function ($node) use (&$list, &$type, $lang) {

                if ($str = $this->getCommitteeType($node)) {

                    // If the current node introduces a committee type, we
                    // store it and, until we encounter a new one, all
                    // the next committees will be of that type.
                    $type = $str;

                } else {

                    $matches = $this->match('#com=(\d+)#', $node->attr('href'));
                    $identifier = $matches[1];

                    // Determine the committee name in the current language.
                    $nameVar = 'name_'.$lang;
                    ${$nameVar} = Utf8::strtolower(trim($node->text()));

                    // If this committee has already been added to the list in
                    // a previous iteration, then we only need to add the name
                    // in the current lang, because the rest of the data is
                    // already there. Otherwise, we set everything.
                    if (isset($list[$identifier])) {
                        $list[$identifier][$nameVar] = ${$nameVar};
                    } else {
                        $list[$identifier] = compact('identifier', 'type', "$nameVar");
                    }
                }
            });
        }

        // Transform the associative array to an
        // indexed one before returning it.
        $committees = [];

        foreach ($list as $committee) {
            $committees[] = $committee;
        }

        return $committees;
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
     * Get the name of a political group from a node.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return string|null
     *
     * @throws \Exception if the type cannot be determined.
     */
    protected function getCommitteeType(Crawler $node)
    {
        if ($node->getNode(0)->tagName === 'h4') {

            // The tag contains the French name of the type. We loop
            // on a map to find the associated ‘normalized’ name.
            $str = trim($node->text());

            foreach ($this->types as $needle => $type) {
                if ($str === $needle) return $type;
            }

            throw new Exception('Cannot determine type of committee');
        }
    }
}
