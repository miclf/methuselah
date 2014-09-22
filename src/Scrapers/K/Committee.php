<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the pages of committees of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Committee extends AbstractScraper
{
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
        // Prepare the document so that we will be able
        // to detect empty seats with the DOM crawler.
        $document = $this->prepareDocument($this->getDocument());

        $crawler = parent::getCrawler($document, $charset);

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
     * Insert empty HTML anchors for each empty committee seat.
     *
     * @param  string  $document
     * @return string
     */
    protected function prepareDocument($document)
    {
        return preg_replace('#Si\xE8ge non attribu\xE9#', '<a></a>', $document);
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

            $roles[$name] = $this->getSeats($node);
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
     * Get a list of committee seats from a node set.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function getSeats(Crawler $node)
    {
        $mps = [];
        $group = null;

        // Get all the nodes except the first <b> tag, which stores the role.
        $nodes = $node->filter('b')->eq(0)->nextAll();

        $nodes->each(function ($node) use (&$mps, &$group) {

            if ($name = $this->getGroupName($node)) {

                // If the current node contains the name of a political group, we
                // store it and, starting from here till we encounter a new group
                // name, all the next seats will belong to this group.
                $group = $name;

            } elseif ($mp = $this->getSeat($node)) {

                // Each time we encounter a seat node, we store its data
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
     * Get the info of a committee seat.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array|null
     */
    protected function getSeat(Crawler $node)
    {
        if ($node->getNode(0)->tagName !== 'a') {
            return;
        }

        // By default, both the MP identifier and full name are null.
        // They will stay null if the seat is empty but replaced by
        // other values if the seat has been assigned to someone.
        $identifier = $given_name_surname = null;

        if ($matches = $this->match('#key=([\dO]+)#', $node->attr('href'))) {

            $identifier         = $matches[1];
            $given_name_surname = $this->trim($node->text());
        }

        return compact('identifier', 'given_name_surname');
    }
}
