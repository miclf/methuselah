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
        $mp['legislatures']       = $this->getLegislatures();
        $mp['cv']                 = $this->getCV();

        $mp += $this->getContactDetails();

        $mp['committees']         = $this->getCommittees();

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
    public function getProviderArguments()
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

        return trim($fullName);
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

            // The value we want is the integer at the beginning
            // of the string. Transtyping the value does the job
            // quickly, without having to play with substrings.
            $legislatures[] = (int) $link->nodeValue;
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
     * @return array  An array of strings containing the different parts of the CV
     */
    protected function getCV()
    {
        $content = $this->crawler
            ->filter("h4:contains('CV: ')")
            ->closestElement('table')
            ->filter('p')
            ->text();

        return array_map('trim', explode('   ', $content));
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
        // for each one, if the MP is an primary or a secondary member.
        $links = $this->crawler->filter('h5, a[href*="com.cfm?com="]');

        $categories = ['primary', 'secondary'];
        $category;

        foreach ($links as $i => $node) {

            if ($node->nodeName === 'h5') {
                $category = array_shift($categories);
                continue;
            }

            $url = $node->getAttribute('href');

            if (preg_match('#\d+$#', $url, $matches)) {

                $id = (string) $matches[0];

                $committees[$category][$id] = $node->nodeValue;
            }
        }

        return $committees;
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
