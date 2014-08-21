<?php namespace Pandemonium\Methuselah\Scrapers\K;

use Pandemonium\Methuselah\Crawler\Crawler;
use Pandemonium\Methuselah\DocumentProvider;
use Pandemonium\Methuselah\Scrapers\AbstractScraper;

/**
 * Extract data from the pages of members of the Chamber.
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
     * The list of languages that can be spoken by MPs.
     *
     * @var array
     */
    protected $langs = [
        'Français'    => 'fr',
        'Néerlandais' => 'nl',
    ];

    /**
     * The list of French month names and their associated number.
     *
     * @var array
     */
    protected $months = [
        'janvier'   => '01',
        'février'   => '02',
        'mars'      => '03',
        'avril'     => '04',
        'mai'       => '05',
        'juin'      => '06',
        'juillet'   => '07',
        'août'      => '08',
        'septembre' => '09',
        'octobre'   => '10',
        'novembre'  => '11',
        'décembre'  => '12',
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
     * Scrape the page of a member of the Chamber and extract its information.
     *
     * @return array
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
            'lang'              => $this->getOption('lang', 'fr'),
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

        if (array_key_exists($lang, $this->langs)) {
            return $this->langs[$lang];
        }
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
     * @return array
     */
    protected function getCommittees()
    {
        $committees = [];

        // We get the anchors linking to committees as well as the headings
        // categorizing them. We will then loop on these nodes to guess,
        // for each one, if the MP is a normal or a substitute member.
        $links = $this->crawler->filter('h5, a[href*="com.cfm?com="]');

        $categories = ['normal', 'substitute'];
        $category;

        foreach ($links as $i => $node) {

            // If we encounter a heading, we are starting a new category.
            if ($node->nodeName === 'h5') {
                $category = array_shift($categories);
                continue;
            }

            $url = $node->getAttribute('href');

            // Try to extract a group or committee identifier from the URL.
            // If one is found, it is used as a key and added to the list
            // with the corresponding full name of the group.
            if (preg_match('#\d+$#', $url, $matches)) {

                $id = (string) $matches[0];

                $committees[$category][$id] = $node->nodeValue;
            }
        }

        return $committees;
    }

    /**
     * Extract data from a MP’s CV.
     *
     * @return array|null
     */
    protected function parseCV()
    {
        // If there is no CV data, we will of course not parse anything.
        if (is_null($cv = $this->getCV())) return null;

        // Initialize the array of data that will be returned.
        $data = [
            'gender'        => null,
            'party'         => null,
            'birthdate'     => null,
        ];

        foreach ($cv as $i => $line) {

            // One of the lines tells us the short name of this MP’s party.
            if (starts_with($line, 'Député')) {
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
        if (str_contains($str, ' FDF ')) {
            $party = 'FDF';
        } elseif (str_contains($str, ' du Vlaams Belang')) {
            $party = 'Vlaams Belang';
        } elseif (starts_with($str, 'Député FDF')) {
            $party = 'FDF';
        }

        // If nothing was found yet, we try the normal case.
        if (!isset($party) && preg_match('#\((.+)\)#U', $str, $matches)) {
            $party = $matches[1];
        }

        return $party;
    }

    /**
     * Extract a date from a string.
     *
     * @param  string       $str
     * @return string|null
     */
    protected function extractDate($str)
    {
        if (preg_match('#(\d+)(?:er)? (\S+) (\d+)#', $str, $date)) {

            $day   = str_pad($date[1], '2', '0', STR_PAD_LEFT);
            $month = $this->months[$date[2]];

            return $date[3].'-'.$month.'-'.$day;
        }
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
