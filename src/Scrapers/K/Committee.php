<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Pandemonium\Methuselah\Crawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;
use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the pages of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Committee extends AbstractScraper
{
    /**
     * Character set of the scraped documents.
     *
     * @var string
     */
    protected $charset = 'ISO-8859-1';

    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * The list of roles that MPs can have in groups and
     * committees, mapped to their French names.
     *
     * @var array
     */
    protected $roles = [
        'presidents'      => 'Président',
        'vice-presidents' => 'Vice-Président',
        'members'         => 'Membres Effectifs',
        'substitutes'     => 'Membres Suppléants',
        'nonvoters'       => 'Membres sans voix délibérative',
    ];

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
     * Scrape the page of a committee and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        // Get and store the node set we will work on.
        $this->crawler = $this->getCrawler();

        $committee = [
            'identifier' => (string) $this->getOption('identifier'),
        ];

        // Get the French and Dutch name of the committee.
        $committee += $this->getNames();

        // Add the list of MPs, categorized by role.
        $committee += $this->getRoles();

        return $committee;
    }

    /**
     * Get a DOM crawler prefilled with only the relevant node set.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler($document = null, $charset = null)
    {
        $crawler = parent::getCrawler();

        // We get the second element with a ‘unique’ ID of ‘story’. Yep.
        return $crawler->filter('#story')->eq(1);
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
        $values = [
            'identifier' => $this->getOption('identifier'),
            'lang'       => $lang,
        ];

        return ['k.committee', $values];
    }

    /**
     * Get the French and Dutch name of the committee.
     *
     * @return array
     */
    protected function getNames()
    {
        // We will download the NL version of the page in order
        // to scrape the name of the committee from it.
        list($pattern, $values) = $this->getProviderArguments('nl');

        $nlPage = $this->getDocument($pattern, $values);

        $nlCrawler = parent::getCrawler($nlPage)->filter('#story h4');

        return [
            'name_fr' => trim($this->crawler->filter('h4')->text()),
            'name_nl' => trim($nlCrawler->filter('h4')->text()),
        ];
    }

    /**
     * Get a list of MPs categorized by committee role.
     *
     * @return array
     */
    protected function getRoles()
    {
        $roles = [];

        // Each role’s data is contained inside a paragraph element. We will
        // loop on all of these and, for each one, extract both the name and
        // the list of MPs.
        $this->crawler->filter('p')->each(function ($node) use (&$roles) {

            $name = $this->getRoleName($node);

            $roles[$name] = $this->getMPs($node);
        });

        return $roles;
    }

    /**
     * Get a normalized role name.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return string|null
     */
    protected function getRoleName(Crawler $node)
    {
        // The first <b> tag contains the French name of the role. We
        // loop on a map to find the associated ‘normalized’ name.
        $str = $node->filter('b')->text();

        foreach ($this->roles as $role => $needle) {

            if (starts_with($str, $needle)) return $role;
        }
    }

    /**
     * Get a list of MPs from a node set.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function getMPs(Crawler $node)
    {
        $mps = [];
        $group = null;

        // Get all the nodes except the first <b> tag, which stores the role.
        $nodes = $node->filter('b')->eq(0)->nextAll();

        $nodes->each(function ($node) use (&$mps, &$group) {

            if ($name = $this->getGroupName($node)) {

                // If the current node contains the name of a political group, we
                // store it and, starting from here till we encounter a new group
                // name, all the next MPs will belong to this group.
                $group = $name;

            } elseif ($mp = $this->getMP($node)) {

                // Each time we encounter a MP node, we store its data
                // and associate it to the relevant political group.
                $mps[] = $mp + ['political_group' => $group];
            }
        });

        return $mps;
    }

    /**
     * Get the name of a political group from a node.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return string|null
     */
    protected function getGroupName(Crawler $node)
    {
        if ($node->getNode(0)->tagName === 'b') {
            return rtrim($node->text(), ':');
        }
    }

    /**
     * Get the full name and identifier of a MP.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array|null
     */
    protected function getMP(Crawler $node)
    {
        if ($node->getNode(0)->tagName === 'a') {

            $pattern = '#key=([\dO]+)#';
            $matches = $this->match($pattern, $node->attr('href'));

            return [
                'identifier'         => $matches[1],
                'given_name_surname' => $this->trim($node->text()),
            ];
        }
    }
}
