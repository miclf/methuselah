<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

/**
 * Provide the mapping for the dossier transformer.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
trait MappingTrait
{
    /**
     * The mapping applied by the transformer.
     *
     * @var array
     */
    protected $mapping = [

        // These nodes will move to the root of the new tree.
        '' => [
            // Full title of the dossier
            'intitule.intitule-complet' => 'title',
            // ‘Short’ title
            'intitule-court'  => 'shortTitle',
        ],

        // These nodes will be grouped as metadata.
        'meta' => [
            // Number of the dossier in K format (00K0000)
            'n-du-document'   => 'meta.number',
            // Bicameral number of the dossier
            'chambre-etou-senat.document-principal.0.n-bicam' => 'meta.bicameralNumber',
            // Number of the legislature
            'legislature'     => 'meta.legislature',
            // Type of dossier
            'chambre-etou-senat.document-principal.0.type.code' => [
                'destination' => 'meta.dossierType',
                'dictionary'  => 'dossierTypes',
            ],
            // Current status
            'etat-davancement.chambre-fr' => [
                'destination' => 'meta.status.chamber',
                'dictionary'  => 'dossierStatuses',
            ],
            // Relevant article of the constitution, if any
            'article-constitution.code' => 'meta.constitutionalArticle',
            // Date of submission
            'chambre-etou-senat.document-principal.0.date-de-depot' => [
                'destination' => 'meta.dates.submission',
                'modifier'    => 'dateToIso'
            ],
            // Date of consideration
            'chambre-etou-senat.document-principal.0.prise-en-consideration' => [
                'destination' => 'meta.dates.consideration',
                'modifier'    => 'dateToIso'
            ],
            // Date of distribution among the MPs
            'chambre-etou-senat.document-principal.0.date-de-distribution' => [
                'destination' => 'meta.dates.distribution',
                'modifier'    => 'dateToIso'
            ],
            // Sending date
            'chambre-etou-senat.document-principal.0.date-denvoi' => [
                'destination' => 'meta.dates.sending',
                'modifier'    => 'dateToIso'
            ],
        ],

    ];
}
