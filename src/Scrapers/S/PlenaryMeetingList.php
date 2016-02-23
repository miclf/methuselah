<?php namespace Pandemonium\Methuselah\Scrapers\S;

/**
 * Extract identifiers of current agenda pages
 * of detailed plenary meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class PlenaryMeetingList extends AbstractMeetingListScraper
{
    /**
     * The pattern to use to validate a link and extract data from it.
     *
     * @var string
     */
    protected $linkPattern = '#DATUM=\'(\d{2}/\d{2}/\d{4})\'&ID=(\d+)&TYP=plenag#';

    /**
     * The key to request from the URL repository.
     *
     * @var string
     */
    protected $urlRepositoryKey = 's.agenda_list.plenary_weeks';
}
