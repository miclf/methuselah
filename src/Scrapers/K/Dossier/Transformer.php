<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

/**
 * Reorganize the tree of a dossier to an improved structure.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class Transformer
{
    /**
     * The source tree that will be transformed.
     *
     * @var array
     */
    protected $source = [];

    /**
     * Transform the given tree.
     *
     * @param  array  $source
     * @return array
     */
    public function transform(array $source)
    {
        $this->source = $source;

        return $source;
    }
}
