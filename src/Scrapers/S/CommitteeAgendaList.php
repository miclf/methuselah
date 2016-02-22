<?php namespace Pandemonium\Methuselah\Scrapers\S;

/**
 * Extract identifiers of current agenda pages
 * of detailed committee meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeAgendaList extends AbstractAgendaListScraper
{
    /**
     * The scraper to use to get data from individual weeks.
     *
     * @var string
     */
    protected $meetingListScraper = 'CommitteeMeetingList';

    /**
     * The key to request from the URL repository.
     *
     * @var string
     */
    protected $urlRepositoryKey = 's.agenda_list.committee_weeks';
}
