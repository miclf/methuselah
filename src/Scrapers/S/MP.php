<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Exception;
use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the pages of senators.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MP extends AbstractScraper
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
     * The list of federated entities (French => English).
     *
     * @var array
     */
    protected $origins = [

        'Groupe linguistique français du Parlement de la Région de Bruxelles-Capitale' =>
        'French-speaking group of the Parliament of the Brussels-Capital Region',

        'Parlement de la Communauté française' =>
        'Parliament of the French Community',

        'Parlement de la Communauté germanophone' =>
        'Parliament of the German-speaking Community',

        'Parlement flamand' =>
        'Flemish Parliament',

        'Parlement wallon' =>
        'Walloon Parliament',

    ];

    /**
     * The list of roles that MPs can have in groups and
     * committees, mapped to their French names.
     *
     * @var array
     */
    protected $roles = [
        'president'  => 'Président',
        'member'     => 'Membre',
        'substitute' => 'Suppléant',
    ];

    /**
     * Scrape the page of a senator and extract its information.
     *
     * @return array
     */
    public function scrape()
    {
        $mp = [];

        $this->crawler = $this->getCrawler();

        // Extract relevant data from the different parts of the page.
        $mp['identifier']   = $this->getOption('identifier');
        $mp['legislatures'] = $this->getLegislatures();
        $mp['committees']   = $this->getCommittees();
        $mp['birthdate']    = $this->getBirthdate();

        $mp += $this->getFullNameAndGroup();
        $mp += $this->getTypeAndOrigin();

        return $mp;
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
        return ['s.mp', ['identifier' => $this->getOption('identifier')]];
    }

    /**
     * Get the list of legislatures where the person has been a MP.
     *
     * @return array
     */
    protected function getLegislatures()
    {
        $legislatures = [];

        $links = $this->getLegislatureLinks();

        // We will loop on the anchors and extract the
        // legislature number from each href attribute.
        foreach ($links as $link) {

            $matches = $this->match('#LEG=(\d+)#', $link->getAttribute('href'));

            $legislatures[] = $matches[1];
        }

        // Order the values before returning them.
        sort($legislatures);

        return $legislatures;
    }

    /**
     * Get the HTML anchors of legislatures the MP has participated to.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getLegislatureLinks()
    {
        // We start at the ‘Travail parlementaire’ heading and take
        // the anchors contained in the following table row.
        return $this->crawler
            ->filter("th:contains('Travail parlementaire')")
            ->closestElement('tr')
            ->nextAll()
            ->first()
            ->filter('a');
    }

    /**
     * Get the list of groups and committees of which the MP is a member.
     *
     * @return array|null
     *
     * @throws \Exception if a committee role cannot be identified.
     */
    protected function getCommittees()
    {
        $committees = [];
        $role = null;

        // We get the anchors linking to committees as well as the headings
        // categorizing them by role. Then we loop on these nodes
        // to guess the role(s) of the MP in each group.
        foreach ($this->getCommitteeNodes() as $node) {

            // If we encounter a ‘heading’ (actually implemented via
            // a <u> element), we are starting with a new role.
            if ($node->nodeName === 'u') {

                $role = $this->getCommitteeRole($node->nodeValue);

                continue;
            }

            $url = $node->getAttribute('href');

            // Try to extract a group or committee identifier from the URL.
            // If one is found, it is used as a key and added to the list
            // with the corresponding full name of the group.
            if ($matches = $this->match('#\d+#', $url)) {

                $id = (string) $matches[0];

                // A MP can have multiple roles inside a group, such as
                // 'president' and 'normal member'. These are then
                // always stored as arrays for each committee.
                $committees[$id][] = $role;
            }
        }

        return $committees ?: null;
    }

    /**
     * Get the DOM nodes storing committee roles and
     * identifiers related to the MP.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCommitteeNodes()
    {
        $hasFoundAllNodes = false;

        // Start by getting all the table rows following
        // the one introducing the committee roles.
        $nodes = $this->crawler
            ->filter("th:contains('Appartenance aux commissions')")
            ->closestElement('tr')
            ->nextAll();

        // Determine which table rows are relevant. We will keep all of
        // them till we find one without an inline background color.
        // That’s a weird criterion but it is the best we have.
        $closure = function ($node) use (&$hasFoundAllNodes) {

            if (!$hasFoundAllNodes && is_null($node->attr('bgcolor'))) {
                $hasFoundAllNodes = true;
            }

            return !$hasFoundAllNodes;
        };

        $nodes = $nodes->reduce($closure);

        // Finally, we only keep the <u> and <a> elements, since these
        // are the only ones containing relevant information.
        return $nodes->filter('u, a');
    }

    /**
     * Find a committee role from a string.
     *
     * @param  string  $str
     * @return string
     *
     * @throws \Exception if the role cannot be found.
     */
    protected function getCommitteeRole($str)
    {
        foreach ($this->roles as $role => $needle) {

            if (str_contains($str, $needle)) return $role;
        }

        throw new Exception('Cannot determine role inside committee');
    }

    /**
     * Get the birthdate of the MP.
     *
     * @return string|null
     */
    protected function getBirthdate()
    {
        $node = $this->crawler->filter("td:contains('Né '), td:contains('Née ')");

        // If the relevant table cell exists, we will extract the first
        // date we can find inside it. This is the birthdate of the MP.
        if (count($node)) {

            $str = $this->trim($node->text());

            return ($birthdate = $this->extractDate($str)) ? $birthdate : null;
        }
    }

    /**
     * Get the full name and political group of the MP.
     *
     * @return array
     */
    protected function getFullNameAndGroup()
    {
        // The first <th> of the table contains the full name
        // and the political group, separated by an hyphen.
        // We split this string and return the two parts.
        $str = $this->crawler->filter('th')->text();

        $parts = explode(' - ', $str);

        return [
            'given_name_surname' => trim($parts[0]),
            'political_group'    => trim($parts[1])
        ];
    }

    /**
     * Get the type of senator and, where applicable, his or
     * her parliament or parliamentary group of origin.
     *
     * @return array
     */
    protected function getTypeAndOrigin()
    {
        $type = $origin = null;

        $str = $this->crawler->filter('td[colspan="3"]')->text();

        // If the senator comes from a federated entity, the parliament of
        // origin will be specified between parentheses. So, if we find
        // anything in parentheses, the senator comes from an entity.
        if ($matches = $this->match('#\((.+)\)#', $str)) {

            $type   = 'federated entities';
            $origin = $this->origins[$matches[1]];

        } elseif (str_contains($str, 'coopté')) {
            $type = 'co-opted';
        }

        return compact('type', 'origin');
    }
}
