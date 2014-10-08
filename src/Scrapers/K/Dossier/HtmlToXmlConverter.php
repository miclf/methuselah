<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

use Illuminate\Support\Str;
use Pandemonium\Methuselah\Crawler\Crawler;

/**
 * Convert a HTML page of dossier to an XML tree.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class HtmlToXmlConverter
{
    /**
     * Convert a HTML page of dossier to an XML tree.
     *
     * @param  string  $html  HTML code of the page of the dossier
     * @return string         An XML tree
     */
    public function convert($html)
    {
        $xml = $openTags = [];

        // We will loop on the rows of the HTML table and compare each level
        // of depth with the next one to decide if we need to open, close or
        // just add elements to the current XML node.
        $this->getCrawler($html)->each(function ($row) use (&$xml, &$openTags) {

            list($key, $value, $depth) = $this->parseRow($row);

            $nextDepth = $this->getDepth($row->nextOne());

            if ($nextDepth > $depth) {

                // If the next row is deeper than the current one, we need to
                // open a new element and store its name to be able to close it
                // later. This case always has a null value, so we don’t add it.

                $xml[]      = "<{$key}>";
                $openTags[] = $key;

            } else {

                // Otherwise, the next row has the same level or is less deep
                // than the current one. Or we reached the end of the map. We
                // then simply create an element with its value.

                $xml[] = "<{$key} v=\"{$value}\" />";
            }

            // If the next row is less deep by at least one level, we will
            // close as many elements as needed to fill the gap.
            if (($diff = $depth - $nextDepth) > 0) {

                while ($diff--) {
                    $xml[] = '</'.array_pop($openTags).'>';
                }
            }
        });

        // The XML tree is now complete. We simply wrap it with a root element
        // just to ensure that this element will always be of the same type.
        // This will make this tree easier to crawl and parse.
        $xml = implode('', $xml);

        return '<?xml version="1.0" encoding="utf-8"?><root>'.$xml.'</root>';
    }

    /**
     * Get a DOM crawler containing the
     * rows of the table to parse.
     *
     * @param  string  $html
     * @return \Pandemonium\Methuselah\Crawler\Crawler
     */
    protected function getCrawler($html)
    {
        $crawler = new Crawler;
        $crawler->addHtmlContent($html);

        return $crawler->filter('table > tr');
    }

    /**
     * Extract an array of data from a row.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return array
     */
    protected function parseRow(Crawler $row)
    {
        $cells = $row->children();

        $key   = Str::slug($cells->first()->text());

        $value = $this->cleanValue($cells->last());

        return [$key, $value, $this->getDepth($row)];
    }

    /**
     * Clean the value of a table row.
     *
     * @param  string  $value
     * @return string
     */
    protected function cleanValue($value)
    {
        $map = ['"' => '&quot;', '&' => '&amp;'];

        $value = str_replace(array_keys($map), array_values($map), $value);

        return trim($value);
    }

    /**
     * Get the level of depth of a row.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $row
     * @return int|false
     */
    protected function getDepth(Crawler $row)
    {
        if (!count($row)) return false;

        return substr_count($row->children()->first(), 'class="puce"');
    }
}
