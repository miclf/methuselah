<?php namespace Pandemonium\Methuselah\Scrapers\S;

use Exception;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Extract data from the pages of dossiers of the Senate.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Dossier extends AbstractScraper
{
    /**
     * The list of languages this scraper can extract data in.
     *
     * @var array
     */
    protected $langs = ['fr', 'nl'];

    /**
     * The list of possible document types,
     * mapped to their French names.
     *
     * @var array
     */
    protected $documentTypes = [
        'Proposition de loi'                   => 'LAW_PROPOSAL',
        'Révision de la constitution'          => 'CONSTITUTIONAL_REVISION',
        'Projet transmis par le Sénat'         => 'PROJECT_SENT_BY_SENATE',
        'Amendements'                          => 'AMENDMENTS',
        'Amendementen'                         => 'AMENDMENTS',
        'Rapport fait au nom de la commission' => 'COMMITTEE_REPORT',
        'Texte corrigé par la commission'      => 'UPDATED_BY_COMMITTEE',
        'Texte adopté par la commission'       => 'APPROVED_BY_COMMITTEE',
        'Texte adopté en séance plénière et transmis à la Chambre' =>
        'APPROVED_BY_PLENARY_SENT_TO_CHAMBER',
        'Texte adopté en séance plénière et soumis à la sanction royale' =>
        'APPROVED_BY_PLENARY_SENT_TO_KING',
        'Avis du Conseil d\'Etat'              => 'OPINION_COUNCIL_OF_STATE',
    ];

    /**
     * An array of DOM crawler instances.
     *
     * @var array
     */
    protected $crawlers = [];

    /**
     * Keep track of the different groups of history items.
     *
     * @var array
     */
    protected $historyGroups = [];

    /**
     * Stores the names of history groups when parsing history.
     *
     * @var string
     */
    protected $currentGroupName;

    /**
     * Stores the depths of history groups when parsing history.
     *
     * @var int
     */
    protected $currentDepth;

    /**
     * Scrape the page of a dossier and extract its information.
     *
     * @return array
     *
     * @throws \Exception if the type of a document cannot be recognized.
     */
    public function scrape()
    {
        $this->crawlers = $this->getCrawlers();

        $dossier = [];

        $dossier['meta']      = $this->getMetadata();
        $dossier['keywords']  = $this->extractKeywords();
        $dossier['documents'] = $this->extractDocuments();
        $dossier['history']   = $this->getHistory();
        $dossier['status']    = $this->getStatus();

        return $dossier;
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
        // Split the dossier identifier to get the legislature
        // number and the number of the dossier.
        $matches = $this->match('#(\d+)S(\d+)#', $this->getOption('identifier'));

        $values = [
            'legislatureNumber' => $matches[1],
            'dossierNumber'     => $matches[2],
            'lang'              => $this->getOption('lang', $lang),
        ];

        return ['s.dossier', $values];
    }

    /**
     * Get a DOM crawler for each language.
     *
     * @return array
     */
    protected function getCrawlers()
    {
        $crawlers = [];

        foreach ($this->langs as $lang) {

            list($pattern, $values) = $this->getProviderArguments($lang);

            $page = $this->getDocument($pattern, $values);

            $crawlers[$lang] = $this->getCrawler($page);
        }

        return $crawlers;
    }

    /**
     * Get the metadata of the dossier.
     *
     * @return array
     */
    protected function getMetadata()
    {
        // Get crawlers for the <tr> elements of the first table.
        $rows   = $this->crawlers['fr']->filter('table:first-child tr');
        $nlRows = $this->crawlers['nl']->filter('table:first-child tr');

        // The first row stores the full number of the dossier.
        $data = $this->extractLegislatureAndDossierNumber($rows->first());

        // The next one hosts the title of the dossier.
        $data['title'] = [
            'fr' => trim($rows->textOfNode(1)),
            'nl' => trim($nlRows->textOfNode(1))
        ];

        // The third table row may contain a list of authors.
        $data['authors'] = $this->extractAuthors($rows->last());

        // The fourth one indicates the type of procedure.
        $data['procedure'] = $this->extractProcedureType();

        return $data;
    }

    /**
     * Get the legislature number and the number of the dossier.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function extractLegislatureAndDossierNumber(Crawler $row)
    {
        $identifier = (string) $row->children()->first();

        $matches = $this->match('#(\d+)-(\d+)#', $identifier);

        return [
            'legislature' => $matches[1],
            'number'      => $matches[2]
        ];
    }

    /**
     * Get the list of authors.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array|null
     */
    protected function extractAuthors(Crawler $row)
    {
        $anchors = $row->filter('a');

        if (!count($anchors)) return null;

        // We will loop on all the links and extract their info.
        return $anchors->each(function ($anchor) {

            $matches = $this->match('#ID=(\d+)#', $anchor->attr('href'));

            return [
                'identifier'         => $matches[1],
                'given_name_surname' => trim($anchor->text())
            ];
        });
    }

    /**
     * Get the type of parliamentary procedure.
     *
     * @return string
     */
    protected function extractProcedureType()
    {
        $selector = 'table:nth-of-type(4) tr:nth-child(3) th';
        $cells    = $this->crawlers['fr']->filter($selector);

        return trim($cells->textOfNode(1));
    }

    /**
     * Get the list of keywords.
     *
     * @return array|null
     */
    protected function extractKeywords()
    {
        $data = [];

        foreach ($this->langs as $lang) {

            // All the keywords of the dossier are contained in a single
            // table cell and separated by <br> elements. We explode
            // this string and then clean the array that we got.
            $cell = $this->crawlers[$lang]->filter('table:nth-of-type(2) td');

            if (!count($cell)) return null;

            $keywords = explode('<br>', $cell->html());

            // Trim values and remove empty ones.
            $data[$lang] = array_filter(array_map('trim', $keywords));
        }

        return $data;
    }

    /**
     * Get the list of documents.
     *
     * @return array|null
     *
     * @throws \Exception if the type of a document cannot be recognized.
     */
    protected function extractDocuments()
    {
        // The third table of the page contains the list of documents.
        // We grab all its rows except the first one, which stores
        // the names of the columns. We then simply make a loop.
        $selector = 'table:nth-of-type(3) tr:nth-child(n+2)';
        $rows     = $this->crawlers['fr']->filter($selector);

        return $rows->each(function ($row) {

            $cells = $row->children();

            return [
                'number' => $this->extractDocumentNumber($cells->textOfNode(0)),
                'type'   => $this->getDocumentTypeIdentifier($cells->textOfNode(1)),
                'date'   => $this->parseDate($cells->textOfNode(2)),
                'links'  => $this->parseDocumentLinks($cells->eq(0)),
            ];
        });
    }

    /**
     * Get a normalized document type identifier.
     *
     * @param  string       $type
     * @return string|null
     *
     * @throws \Exception if the type is not recognized.
     */
    protected function getDocumentTypeIdentifier($type)
    {
        $str = trim($type);

        // The $type variable contains the French name of the type. We
        // loop on a map to find the associated ‘normalized’ name.
        foreach ($this->documentTypes as $needle => $identifier) {
            if ($str === $needle) return $identifier;
        }

        throw new Exception('Cannot determine type of document');
    }

    /**
     * Extract a document number from a string.
     *
     * @param  string  $str
     * @return string
     */
    protected function extractDocumentNumber($str)
    {
        $matches = $this->match('#\d+-\d+(?:/\d+)#', trim($str));

        return $matches[0];
    }

    /**
     * Find document links and extract info from them.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function parseDocumentLinks(Crawler $node)
    {
        $data = $found = [];

        // We will loop on all the links and extract their info.
        $node->filter('a')->each(function ($anchor) use (&$data, &$found) {

            $url = $this->getDocumentUrl($anchor);

            // We look for a document format, normally specified inside
            // parentheses in the title attribute of each anchor.
            $matches = $this->match('#\((.+)\)#', $anchor->attr('title'));

            // The website of the Senate sometimes shows multiple links to
            // the exact same document. We then need to keep track of the
            // ones we already added so that we don’t store duplicates.
            // We will also skip the link if we can’t find its format.
            if (in_array($url, $found) || !$matches) {
                return;
            }

            $found[] = $url;

            $data[] = [
                'format' => strtolower($matches[1]),
                'url'    => $url
            ];
        });

        return $data;
    }

    /**
     * Get the absolute URL of a document.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $anchor
     * @return string
     */
    protected function getDocumentUrl(Crawler $anchor)
    {
        // If the link goes to the site of the Chamber, we return
        // its URL as is. Otherwise, it has a relative URL to the
        // site of the Senate so we prepend the domain name.
        $href = $anchor->attr('href');

        if (str_contains($href, 'lachambre.be')) {
            return $href;
        }

        return 'http://senate.be'.$href;
    }

    /**
     * Get the history items of the dossier.
     *
     * @return array
     */
    protected function getHistory()
    {
        // The fourth table of the page stores the history of the
        // dossier so far. We will loop on all its rows except
        // the first three, which contain no history info.
        $selector = 'table:nth-of-type(4) tr:nth-child(n+4)';
        $rows     = $this->crawlers['fr']->filter($selector);
        $nlRows   = $this->crawlers['nl']->filter($selector);

        $history = [];

        $rows->each(function ($row, $i) use (&$history, $nlRows) {

            // Skip the row if it contains no history data.
            if (!$this->hasHistoryData($row)) {
                return;
            }

            $cells   = $row->children();
            $nlCells = $nlRows->eq($i)->children();

            // Here we gather the basic information of the history item.
            $data = [
                'group_name' => $this->currentGroupName,
                'date'       => $this->parseDate($cells->textOfNode(0)),
                'content_fr' => $this->getItemContent($cells),
                'content_nl' => $this->getItemContent($nlCells),
            ];

            // Store the data we got, plus any extra data we could obtain.
            $history[] = $data + $this->getExtraRowData($row);
        });

        return $history;
    }

    /**
     * Determine if a row contains history data.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return bool
     */
    protected function hasHistoryData(Crawler $row)
    {
        // A row starting a new group contains no history data.
        if ($this->isStartingNewGroup($row)) {
            return false;
        }

        $this->checkGroupChange($row);

        return true;
    }

    /**
     * Determine if the current history row starts a new group.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return bool
     */
    protected function isStartingNewGroup(Crawler $row)
    {
        if (is_null($row->attr('bgcolor'))) {
            return false;
        }

        $name  = $this->trim($row->text());
        $depth = $row->filter('td:first-child')->attr('colspan');

        // We save the name of the new group for later reuse.
        $this->currentGroupName = $name;
        $this->historyGroups[$depth] = $name;

        return true;
    }

    /**
     * Determine if the current history row causes a group
     * change and update the relevant properties.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return void
     */
    protected function checkGroupChange(Crawler $row)
    {
        $rowDepth = $this->getRowDepth($row);

        if ($rowDepth == $this->currentDepth) return;

        // If the depth of the current history row is different than the
        // one of the previous row, it means that the group has changed.
        // We then try to update that name according to the new depth.
        // By default, the group name is reset. We then check if we
        // previsouly had a named group at that depth.
        $this->currentGroupName = null;
        $this->currentDepth     = $rowDepth;

        if (isset($this->historyGroups[$rowDepth])) {
            $this->currentGroupName = $this->historyGroups[$rowDepth];
        }
    }

    /**
     * Calculate the depth of a history row.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return int
     */
    protected function getRowDepth(Crawler $row)
    {
        $colspan = $row->filter('td:nth-child(3)')->attr('colspan');

        return 5 - $colspan;
    }

    /**
     * Extract any special data an history row may contain.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function getExtraRowData(Crawler $row)
    {
        $extra = [];

        if ($this->isReferencingDocument($row)) {

            $str = $row->children()->last()->text();

            $extra['document'] = $this->extractDocumentNumber($str);
        }

        return $extra;
    }

    /**
     * Checks if the given row contains a link to a document.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return bool
     */
    protected function isReferencingDocument(Crawler $row)
    {
        return (bool) count($this->parseDocumentLinks($row));
    }

    /**
     * Get the main content of a history item.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $cells
     * @return string
     */
    protected function getItemContent(Crawler $cells)
    {
        // We grab the content of the correct table cell and
        // transform its line breaks before returning it.
        $html = $cells->eq(2)->html();

        $html = str_replace('<br>', "\n", $html);

        return trim($html);
    }

    /**
     * Get the current status data of the dossier.
     *
     * @return array
     */
    protected function getStatus()
    {
        // The fifth table of the page stores the status of the
        // dossier so far. We will loop on all its rows except
        // the first two, which contain no useful info.
        $selector = 'table:nth-of-type(5) tr:nth-child(n+3)';
        $rows     = $this->crawlers['fr']->filter($selector);
        $nlRows   = $this->crawlers['nl']->filter($selector);

        $status = [];

        $rows->each(function ($row, $i) use (&$status, $nlRows) {

            $cells = $row->children();

            $status[] = [
                'group_name' => trim($cells->textOfNode(0)),
                'status_fr'  => trim($cells->textOfNode(1)),
                'status_nl'  => trim($nlRows->eq($i)->children()->textOfNode(1)),
                'dates'      => $this->parseStatusDates($cells->textOfNode(2)),
            ];
        });

        return $status;
    }

    /**
     * Parse a set of dates from the status table.
     *
     * @param  string  $str
     * @return array|null
     */
    protected function parseStatusDates($str)
    {
        $dates = [];

        foreach (explode(',', $str) as $date) {

            if ($date = $this->trim($date)) {
                $dates[] = $this->parseDate($date);
            }
        }

        return count($dates) ? $dates : null;
    }

    /**
     * Convert a date to ISO 8601.
     *
     * @param  string  $date
     * @return string
     */
    protected function parseDate($date)
    {
        $parts = explode('/', trim($date));

        return
            $parts[2].'-'.
            str_pad($parts[1], 2, '0', STR_PAD_LEFT).'-'.
            str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    }
}
