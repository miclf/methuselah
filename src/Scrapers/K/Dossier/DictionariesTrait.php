<?php namespace Pandemonium\Methuselah\Scrapers\K\Dossier;

/**
 * Provide the dictionaries for the dossier transformer.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
trait DictionariesTrait
{
    /**
     * A dictionary of types of dossiers.
     *
     * @var array
     */
    protected $dossierTypes = [
        '05' => 'LAW_PROPOSAL',
        '06' => 'RESOLUTION_PROPOSAL',
        '07' => 'DECLARATION_PROPOSAL',
        '08' => 'CONSTITUTIONAL_REVISION_PROPOSAL',
        '11' => '???',
        '15' => 'COMMUNICATION_TO_PARLIAMENT',
        '23' => 'REPORT',
        '31' => 'AMENDMENT',
        '36' => 'OPINION_COUNCIL_OF_STATE',
        '50' => 'TABLE_OR_LIST',
        '64' => 'CONCERTATION_COMMITTEE_DECISION',
        '66' => 'ELECTIONS',
    ];

    /**
     * A dictionary of global dossier statuses.
     *
     * @var array
     */
    protected $dossierStatuses = [
        'PENDANT CHAMBRE' => 'PENDING',
        'RETIRE CHAMBRE'  => 'REMOVED',
        'CADUQUE CHAMBRE' => 'VOID',
    ];
}
