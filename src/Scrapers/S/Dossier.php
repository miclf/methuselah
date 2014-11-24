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
     * Stores the data that has been scraped.
     *
     * @var array
     */
    protected $data = [];

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
        'Projet de loi'                        => 'LAW_PROJECT',
        'Révision de la constitution'          => 'CONSTITUTIONAL_REVISION',
        'Projet transmis par le Sénat'         => 'PROJECT_SENT_BY_SENATE',
        'Amendement'                           => 'AMENDMENT',
        'Amendements'                          => 'AMENDMENTS',
        'Amendementen'                         => 'AMENDMENTS',
        'Rapport fait au nom de la commission' => 'COMMITTEE_REPORT',
        'Texte corrigé par la commission'      => 'UPDATED_BY_COMMITTEE',
        'Texte adopté par la commission'       => 'APPROVED_BY_COMMITTEE',
        'Texte adopté en séance plénière et transmis à la Chambre' =>
        'APPROVED_BY_PLENARY-SENT_TO_CHAMBER',
        'Texte adopté en séance plénière et transmis au Sénat' =>
        'APPROVED_BY_PLENARY-SENT_TO_SENATE',
        'Texte adopté en séance plénière et soumis à la sanction royale' =>
        'APPROVED_BY_PLENARY-SENT_TO_KING',
        'Liste'                                => 'LIST',
        'Avis du Conseil d\'Etat'              => 'OPINION_COUNCIL_OF_STATE',
    ];

    /**
     * The list of possible types of procedures,
     * mapped to their French names.
     *
     * @var array
     */
    protected $procedureTypes = [
        'UNICAMERAL' =>
        'Monocamérale Sénat',
        'BICAMERAL-FROM-SENATE' =>
        'Bicaméral, initiative Sénat',
        'PARTLY_BICAMERAL-FROM-SENATE_81' =>
        '(81) Partiellement bicaméral, initiative Sénat',
        'URGENCY_PROCEDURE_78-79-80' =>
        '(78+79+80) Procédure d\'évocation (urgence)',
        'FREE_PROCEDURE' =>
        'Procédure libre',
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

        $this->data['meta']      = $this->getMetadata();
        $this->data['keywords']  = $this->getKeywords();
        $this->data['documents'] = $this->getDocuments();
        $this->data['history']   = $this->getHistory();
        $this->data['status']    = $this->getStatus();

        return $this->data;
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
     * @return string|null
     *
     * @throws \Exception if the type of procedure cannot be recognized.
     */
    protected function extractProcedureType()
    {
        $selector = 'table:nth-of-type(4) tr:nth-child(3) th:nth-child(2)';
        $cell     = $this->crawlers['fr']->filter($selector);

        if (!count($cell)) {
            return null;
        }

        $str = trim($cell->text());

        // The $type variable contains the French name of the type. We
        // loop on a map to find the associated ‘normalized’ name.
        foreach ($this->procedureTypes as $type => $needle) {
            if ($str === $needle) return $type;
        }

        throw new Exception('Cannot determine type of procedure');
    }

    /**
     * Extract the list of keywords.
     *
     * @return array|null
     */
    protected function getKeywords()
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
    protected function getDocuments()
    {
        // The third table of the page contains the list of documents.
        // We grab all its rows except the first one, which stores
        // the names of the columns. We then simply make a loop.
        $selector = 'table:nth-of-type(3) tr:nth-child(n+2)';
        $rows     = $this->crawlers['fr']->filter($selector);

        return $rows->each(function ($row) {

            $cells = $row->children();

            $links = $this->parseDocumentLinks($cells->eq(0));

            return [
                'number' => $this->listDocumentNumbers($links)[0],
                'type'   => $this->getDocumentTypeIdentifier($cells->textOfNode(1)),
                'date'   => $this->parseDate($cells->textOfNode(2)),
                'links'  => $links,
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
     * Get document numbers from a list of documents.
     *
     * @param  array  $links
     * @return array
     */
    protected function listDocumentNumbers(array $links)
    {
        // Get the label of each link.
        $numbers = array_map(function ($link) {return $link['label'];}, $links);

        // Remove duplicates and reset the keys of the array.
        return array_values(array_unique($numbers));
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

            $pattern = '#(\d+-\d+(?:/\d+)?).+\((.+)\)$#';
            $matches = $this->match($pattern, $anchor->attr('title'));

            // The website of the Senate sometimes shows multiple links to
            // the exact same document. We then need to keep track of the
            // ones we already added so that we don’t store duplicates.
            // We will also skip the current doc if it can’t parsed.
            if ($matches && !in_array($url, $found)) {

                $data[] = [
                    'url'    => $url,
                    'label'  => $matches[1],
                    'format' => strtolower($matches[2]),
                ];

                $found[] = $url;
            }
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
        $selector = $this->getHistorySelector();

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
     * Get the CSS selector matching history rows.
     *
     * @return string
     */
    protected function getHistorySelector()
    {
        // We will skip the first 2 or 3 rows (which contain no history info),
        // depending on if there is a row storing the type of procedure.
        $skip = ($this->data['meta']['procedure'] !== null) ? 4 : 3;

        return 'table:nth-of-type(4) tr:nth-child(n+'.$skip.')';
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
        if ($this->isStartingNewGroup($row) || count($row->children()) !== 4) {
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

        // If we reached the minimum possible depth and have a
        // procedure type, we use it as the name of the group.
        $procedureType = $this->data['meta']['procedure'];

        if ($rowDepth === 1 && $procedureType !== null) {
            $this->currentGroupName = $procedureType;
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

        // Get document numbers referenced by the row.
        if ($links = $this->parseDocumentLinks($row)) {
            $extra['documents'] = $this->listDocumentNumbers($links);
        }

        return $extra;
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

        return trim(strip_tags($html));
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
        // We convert the DD/MM/YYYY format to YYYY-MM-DD.
        $parts = explode('/', trim($date));

        return
            $parts[2].'-'.
            str_pad($parts[1], 2, '0', STR_PAD_LEFT).'-'.
            str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    }
}
