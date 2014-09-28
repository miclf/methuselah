<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the pages of members of the Chamber.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MP extends AbstractScraper
{
    /**
     * An instance of a DOM crawler.
     *
     * @var \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected $crawler;

    /**
     * The list of languages that can be spoken by MPs.
     *
     * @var array
     */
    protected $langs = [
        'Français'    => 'fr',
        'Néerlandais' => 'nl',
    ];

    /**
     * The list of roles that MPs can have in groups and
     * committees, mapped to their French names.
     *
     * @var array
     */
    protected $roles = [
        'president'  => 'Président',
        'member'     => 'Membre Effectif',
        'substitute' => 'Membre Suppléant',
        'nonvoter'   => 'Membre sans voix délibérative',
    ];

    /**
     * Scrape the page of a member of the Chamber and extract its information.
     *
     * @return array
     *
     * @throws \Exception if a committee role cannot be identified.
     */
    public function scrape()
    {
        $mp = [];

        $this->crawler = $this->getCrawler();

        // Extract relevant data from the different parts of the page.
        $mp['given_name_surname'] = $this->getFullName();
        $mp['identifier']         = $this->getOption('identifier');
        $mp['legislatures']       = $this->getLegislatures();
        $mp['committees']         = $this->getCommittees();

        $mp += $this->getContactDetails();
        $mp += $this->parseCV();

        ksort($mp);

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
        $values = [
            'identifier'        => $this->getOption('identifier'),
            'lang'              => 'fr',
            'legislatureNumber' => $this->getOption('legislature_number', 54),
        ];

        return ['k.mp', $values];
    }

    /**
     * Get the given name and surname of the MP.
     *
     * @return string
     */
    protected function getFullName()
    {
        // The data is contained inside the first <center> element of the page.
        $fullName = $this->crawler->filter('center')->text();

        return $this->trim($fullName);
    }

    /**
     * Get the list of legislatures where the person has been a MP.
     *
     * @return array
     */
    protected function getLegislatures()
    {
        $legislatures = [];

        $links = $this->crawler->filter('[class="menu"]:nth-of-type(1) a');

        foreach ($links as $link) {

            // The value we want is the number at the beginning of the string.
            // Transtyping the value does the job quickly, without having to
            // play with substrings. We then store the value as a string.
            $legislatures[] = (string) intval($link->nodeValue);
        }

        // Order the values before returning them.
        sort($legislatures);

        return $legislatures;
    }

    /**
     * Get the contact details of the MP.
     *
     * @return array
     */
    protected function getContactDetails()
    {
        $data = [];

        // We will loop on all the paragraphs and extract
        // the relevant pieces of data from each of them.
        $this->getContactDetailsNodes()->each(function ($node) use (&$data) {

            $html = $node->html();

            if (str_contains($html, 'Langue:')) {

                // First, try to extract the language spoken by the MP.
                $data['lang'] = $this->extractLanguage($html);

            } elseif (str_contains($html, 'Adresse:')) {

                // Get the address if one exists.
                $data['address'] = $this->extractAddress($html);

            } elseif (str_contains($html, ['email', 'website'])) {

                // Try to get the official e-mail address as well as a website.
                $data += $this->extractEmailAndWebsite($node);
            }
        });

        return $data;
    }

    /**
     * Get the HTML nodes storing the contact details of the MP.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getContactDetailsNodes()
    {
        return $this->crawler
            ->filter("[alt='Picture']")
            ->closestElement('table')
            ->filter('p');
    }

    /**
     * Get the language spoken by the MP.
     *
     * @param  string       $html
     * @return string|null
     */
    protected function extractLanguage($html)
    {
        $lang = trim($this->removeTags($html));

        return isset($this->langs[$lang]) ? $this->langs[$lang] : null;
    }

    /**
     * Get the official contact address of the MP.
     *
     * @param  string       $html
     * @return string|null
     */
    protected function extractAddress($html)
    {
        $address = trim($this->removeTags($html));

        return $address ?: null;
    }

    /**
     * Get the official e-mail address of the MP as well as a website.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function extractEmailAndWebsite(Crawler $node)
    {
        $data = ['email' => null, 'website' => null];

        foreach ($node->filter('a') as $link) {

            if ($text = trim($link->nodeValue)) {

                $key = str_contains($text, '@') ? 'email' : 'website';
                $data[$key] = $text;
            }
        }

        return $data;
    }

    /**
     * Get the short ‘CV’ of the MP.
     *
     * @return array|null  An array of strings containing the different parts of the CV
     */
    protected function getCV()
    {
        $content = $this->crawler
            ->filter("h4:contains('CV: ')")
            ->closestElement('table')
            ->filter('p')
            ->text();

        // Return early if the trimmed content is an empty string.
        if (!$content = trim($content)) return null;

        // The sentences of the text are usually (read: not always) separated
        // by a series of two or three consecutive space characters. We will
        // use this fact to convert the text to an array of sentences.
        return preg_split('#\s{2,}#', $content);
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
        $nodes = $this->crawler->filter('h5, a[href*="com.cfm?com="]');

        foreach ($nodes as $node) {

            // If we encounter a heading, we are starting with a new role.
            if ($node->nodeName === 'h5') {

                $role = $this->getCommitteeRole($node->nodeValue);

                continue;
            }

            $url = $node->getAttribute('href');

            // Try to extract a group or committee identifier from the URL.
            // If one is found, it is used as a key and added to the list
            // with the corresponding full name of the group.
            if ($matches = $this->match('#\d+$#', $url)) {

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
     * Extract data from a MP’s CV.
     *
     * @return array
     */
    protected function parseCV()
    {
        // Initialize the array of data that will be returned.
        $data = [
            'gender'    => null,
            'party'     => null,
            'birthdate' => null,
        ];

        // If there is no CV data, we will of course not parse anything.
        if (is_null($cv = $this->getCV())) return $data;

        foreach ($cv as $line) {

            // One of the lines tells us the short name of this MP’s party.
            if (starts_with($line, 'Député') && !isset($data['party'])) {

                $data['party'] = $this->extractParty($line);
            }

            if (starts_with($line, ['Né', 'né']) || str_contains($line, '. Né')) {

                // Guess if the MP is a man or a woman.
                $data['gender'] = starts_with($line, ['Née ', 'née ']) ? 'f' : 'm';

                // Try to get her or his date of birth.
                $data['birthdate'] = $this->extractDate($line);
            }
        }

        return $data;
    }

    /**
     * Extract the short name of a party from a string.
     *
     * @param  string       $str
     * @return string|null
     */
    protected function extractParty($str)
    {
        $party = null;

        // We first test special cases.
        if (starts_with($str, 'Député FDF') || str_contains($str, ' FDF ')) {
            return 'FDF';
        } elseif (str_contains($str, ' du Vlaams Belang')) {
            return 'Vlaams Belang';
        }

        // If nothing was found yet, we try the normal case.
        if ($matches = $this->match('#\((.+)\)#U', $str)) {
            $party = $matches[1];
        }

        return $party;
    }

    /**
     * Remove XML-like tags from a string.
     *
     * @param  string  $str
     * @return string
     */
    protected function removeTags($str)
    {
        return preg_replace('#<(\w+)>.+</\1>#', '', $str);
    }
}
