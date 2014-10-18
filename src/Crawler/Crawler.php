<?php namespace Pandemonium\Methuselah\Crawler;

use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class Crawler extends SymfonyCrawler
{
    /**
     * Returns the node that is the nearest ancestor with a given tag name.
     *
     * @param  string                                   $tagName  The tag name
     * @return \Pandemonium\Methuselah\Crawler\Crawler  A new instance of the Crawler with the node that was found, or an empty Crawler if nothing was found.
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    public function closestElement($tagName)
    {
        $this->throwIfEmpty();

        $node = $this->getNode(0);

        while ($node = $node->parentNode) {
            if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === $tagName) {
                return new static($node, $this->uri);
            }
        }

        return new static(null, $this->uri);
    }

    /**
     * Returns the next sibling node of the current selection.
     *
     * @return \Pandemonium\Methuselah\Crawler\Crawler  A Crawler instance with the next sibling node
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    public function nextOne()
    {
        $this->throwIfEmpty();

        return new static($this->siblingOne($this->getNode(0)), $this->uri);
    }

    /**
     * Returns the previous sibling node of the current selection.
     *
     * @return \Pandemonium\Methuselah\Crawler\Crawler  A Crawler instance with the previous sibling node
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    public function previousOne()
    {
        $this->throwIfEmpty();

        return new static($this->siblingOne($this->getNode(0), 'previousSibling'), $this->uri);
    }

    /**
     * Return the first next or previous element node.
     *
     * @param \DOMElement  $node
     * @param string       $siblingDir
     *
     * @return \DOMElement|null
     */
    protected function siblingOne($node, $siblingDir = 'nextSibling')
    {
        while ($node = $node->$siblingDir) {
            if ($node->nodeType === 1) return $node;
        }
    }

    /**
     * Test if the current node is empty.
     *
     * @return void
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    protected function throwIfEmpty()
    {
        if (!count($this)) {
            throw new \InvalidArgumentException('The current node list is empty.');
        }
    }

    /**
     * Returns the first node of the list as HTML.
     *
     * @return  string  The node html
     *
     * @throws \InvalidArgumentException when current node is empty
     */
    public function __toString()
    {
        return $this->html();
    }
}
