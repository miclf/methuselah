<?php namespace Pandemonium\Methuselah\Crawler;

use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class Crawler extends SymfonyCrawler
{
    /**
     * Returns the node that is the nearest ancestor with a given tag name.
     *
     * @param  string                           $tagName  The tag name
     * @return \Pandemonium\Methuselah\Crawler  A new instance of the Crawler with the nodethat was found, or an empty Crawler if nothing was found.
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    public function closestElement($tagName)
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }

        $node = $this->getNode(0);

        while ($node = $node->parentNode) {
            if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === $tagName) {
                return new static($node, $this->uri);
            }
        }

        return new static(null, $this->uri);
    }
}
