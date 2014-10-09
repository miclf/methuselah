<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Convert an XML tree of dossier to a multidimensional PHP array.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class XmlToArrayConverter
{
    /**
     * Paths in the XML tree that always need to
     * be considered as storing multiple values.
     *
     * @var array
     */
    protected $arrayProperties = [
        // Main document(s)
        'chambre-etou-senat > document-principal',
        // Authors of a main document
        'chambre-etou-senat > document-principal > auteur',
        // Other documents
        'chambre-etou-senat > document-principal > sous-documents > documents-suivants',
        // Other dossiers that are linked to this one
        'chambre-etou-senat > document-principal > documents-jointslies',
        // Authors of other documents
        'chambre-etou-senat > document-principal > sous-documents > documents-suivants > auteurs',
        // Committees and other similar stuff
        'toutes-les-commissions > commission',
        // Reporters of the dossier inside a committee
        'toutes-les-commissions > commission > rapporteur',
        // Agenda items of a committee
        'toutes-les-commissions > commission > calendrier',
        // Eurovoc descriptors
        'descripteurs-mots-cles > descripteurs-eurovoc',
    ];

    /**
     * Convert an XML tree of dossier to
     * a multidimensional PHP array.
     *
     * @param  string  $xml
     * @return array
     */
    public function convert($xml)
    {
        $this->normalizeArrayProperties();

        $crawler = $this->newCrawler($xml);

        // We convert the XML document to a tree of PHP arrays. Once
        // it’s done, we remove the root element from the returned
        // result, because it was only useful in an XML context.
        return $this->parse($crawler)['root'];
    }

    /**
     * Recursively convert an XML tree to a tree of PHP arrays.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $crawler
     * @return array
     */
    public function parse($crawler)
    {
        $tree = [];

        $crawler->each(function ($node) use (&$tree) {

            if ($this->hasChildren($node)) {
                $nodeContent = $this->parse($node->children());
            } else {
                $nodeContent = html_entity_decode($node->attr('v'));
            }

            $name = $node->getNode(0)->tagName;

            if ($this->hasTwins($node) || $this->isArrayProperty($node)) {
                $tree[$name][] = $nodeContent;
            } else {
                $tree[$name] = $nodeContent;
            }
        });

        return $tree;
    }

    /**
     * Check if an element has at least one sibling of the same name.
     *
     * @param  \DOMElement|\Symfony\Component\DomCrawler\Crawler  $node
     * @return bool
     *
     * @throws \Exception if $node has an incorrect type.
     */
    protected function hasTwins($node)
    {
        if ($node instanceof Crawler) {
            $node = $node->getNode(0);
        } elseif (!($node instanceof \DOMElement)) {
            throw new Exception('$node must be an instance of DOMElement or Crawler');
        }

        // We do not take the parent node if we are at the top of the DOM tree
        $parent = ($node->tagName !== 'root') ? $node->parentNode : $node;

        $selector = $parent->tagName.'>'.$node->tagName;

        $twins = $this->newCrawler($parent)->filter($selector);

        return (count($twins) > 1);
    }

    /**
     * Check if a given node has children elements.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return boolean
     */
    protected function hasChildren(Crawler $node)
    {
        return (bool) count($node->children());
    }

    /**
     * Get an array of all of the ancestor’s node names of a given node.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return array
     */
    protected function getNodePath(Crawler $node)
    {
        $segments = [$node->getNode(0)->tagName];

        foreach ($node->parents() as $parent) {
            array_unshift($segments, $parent->tagName);
        }

        return $segments;
    }

    /**
     * Check if the current element must always
     * be able to store multiple values.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $node
     * @return boolean
     */
    protected function isArrayProperty(Crawler $node)
    {
        $path = implode(' > ', $this->getNodePath($node));

        return in_array($path, $this->arrayProperties);
    }

    /**
     * Normalize paths of arrayProperties
     * from the root node.
     *
     * @return array
     */
    protected function normalizeArrayProperties()
    {
        $closure = function ($path) {

            if (!starts_with($path, 'root')) return "root > {$path}";

            return $path;
        };

        $this->arrayProperties = array_map($closure, $this->arrayProperties);
    }

    /**
     * Create a new DOM Crawler instance.
     *
     * @param  mixed  $node  A Node to use as the base for the crawling
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function newCrawler($node = null)
    {
        return new Crawler($node);
    }
}
