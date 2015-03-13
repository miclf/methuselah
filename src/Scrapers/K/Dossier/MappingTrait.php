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

        // These nodes will be grouped as metadata.
        'meta' => [
            // Full title of the dossier
            'intitule.intitule-complet' => 'title',
            // ‘Short’ title
            'intitule-court'  => 'shortTitle',
            // Number of the dossier in K format (00K0000)
            'n-du-document'   => 'number',
            // Bicameral number of the dossier
            'chambre-etou-senat.document-principal.0.n-bicam' => 'bicameralNumber',
            // Number of the legislature
            'legislature'     => 'legislature',
            // Type of dossier
            'chambre-etou-senat.document-principal.0.type.code' => [
                'destination' => 'dossierType',
                'dictionary'  => 'dossierTypes',
            ],
            // Current status
            'etat-davancement.chambre-fr' => [
                'destination' => 'status.chamber',
                'dictionary'  => 'dossierStatuses',
            ],
            // Relevant article of the constitution, if any
            'article-constitution.code' => 'constitutionalArticle',
            // Date of submission
            'chambre-etou-senat.document-principal.0.date-de-depot' => [
                'destination' => 'dates.submission',
                'modifier'    => 'dateToIso'
            ],
            // Date of consideration
            'chambre-etou-senat.document-principal.0.prise-en-consideration' => [
                'destination' => 'dates.consideration',
                'modifier'    => 'dateToIso'
            ],
            // Date of distribution among the MPs
            'chambre-etou-senat.document-principal.0.date-de-distribution' => [
                'destination' => 'dates.distribution',
                'modifier'    => 'dateToIso'
            ],
            // Sending date
            'chambre-etou-senat.document-principal.0.date-denvoi' => [
                'destination' => 'dates.sending',
                'modifier'    => 'dateToIso'
            ],
        ],

        // Mapping to apply to main documents.
        'main_document' => [
            // Number of the document in K format (00K0000000)
            'n-du-document' => 'number',
            // Type of document
            'type.code' => [
                'destination' => 'dossierType',
                'dictionary'  => 'dossierTypes',
            ],
            // Date of submission
            'date-de-depot' => [
                'destination' => 'dates.submission',
                'modifier'    => 'dateToIso'
            ],
            // Date of distribution among the MPs
            'date-de-distribution' => [
                'destination' => 'dates.distribution',
                'modifier'    => 'dateToIso'
            ],
        ],

    ];
}
