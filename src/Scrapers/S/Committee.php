<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the pages of committees of the Senate.
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
        'presidents'  => 'Président',
        'first_vice_presidents' => 'Premier Vice-Président',
        'second_vice_presidents' => 'Deuxième Vice-Président',
        'members'     => 'Membres',
        'substitutes' => 'Membres Suppléants',
    ];

    /**
     * Scrape the page of a committee and extract its information.
     *
     * @return array
     *
     * @throws \Exception if a role cannot be determined.
     */
    public function scrape()
    {
        $this->crawler = $this->getCrawler();

        $committee = [
            'identifier' => (string) $this->getOption('identifier')
        ];

        // Get the French and Dutch name of the committee.
        $committee += $this->getNames();

        // Add the list of MPs, categorized by role.
        $committee += $this->getRoles();

        return $committee;
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

        return ['s.committee', $values];
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

        $nlCrawler = $this->getCrawler($nlPage);

        return [
            'name_fr' => $this->crawler->filter('h1')->text(),
            'name_nl' => $nlCrawler->filter('h1')->text(),
        ];
    }

    /**
     * Get a list of MPs categorized by committee role.
     *
     * @return array
     *
     * @throws \Exception if a role cannot be determined.
     */
    protected function getRoles()
    {
        $roles = [];
        $name  = null;

        $nodes = $this->crawler->filter('h3, ul');

        // Each role’s data is contained inside an unordered list. We will
        // loop on all of them and, for each one, extract both the name and
        // the list of MPs.
        $nodes->each(function ($node) use (&$roles, &$name) {

            // If the current node introduces a new role, we
            // store it and, until we encounter a new one,
            // all the next seats will have that role.
            if ($str = $this->getRoleName($node)) {

                $name = $str;

                return;
            }

            // Otherwise, the node contains an <ul> element that we will
            // extract the seats data from. The conditional statement
            // is here to work around a bug where the same list is
            // displayed multiple times on a page.
            if (!isset($roles[$name])) {
                $roles[$name] = $this->getSeats($node);
            }
        });

        return $roles;
    }

    /**
     * Get a normalized role name.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return string|null
     *
     * @throws \Exception if the role cannot be determined.
     */
    protected function getRoleName(Crawler $node)
    {
        if ($node->getNode(0)->tagName !== 'h3') return;

        // The tag contains the French name of the role. We loop
        // on a map to find the associated ‘normalized’ name.
        $str = $node->text();

        foreach ($this->roles as $role => $needle) {
            if ($str === $needle) return $role;
        }

        throw new Exception('Cannot determine role inside committee');
    }

    /**
     * Get a list of committee seats from a node set.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $list
     * @return array
     */
    protected function getSeats(Crawler $list)
    {
        $seats = [];

        $list->filter('li')->each(function ($node) use (&$seats) {

            if ($seat = $this->getSeat($node)) {
                $seats[] = $seat;
            }
        });

        return $seats;
    }

    /**
     * Get the info of a committee seat.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array|null
     */
    protected function getSeat(Crawler $node)
    {
        $regex = '#ID=(\d+)&amp;LANG=fr">(.+)</a> \((.+)\)#';

        if ($matches = $this->match($regex, $node->html())) {

            return [
                'identifier'         => $matches[1],
                'surname_given_name' => $matches[2],
                'political_group'    => $matches[3]
            ];
        }
    }
}
