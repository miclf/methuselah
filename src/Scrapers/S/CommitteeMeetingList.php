<?php namespace Pandemonium\Methuselah\Scrapers\S;

/**
 * Extract identifiers of current agenda pages
 * of detailed committee meetings.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeMeetingList extends AbstractMeetingListScraper
{
    /**
     * The pattern to use to validate a link and extract data from it.
     *
     * @var string
     */
    protected $linkPattern = '#ID=(\d+)&TYP=comag#';

    /**
     * The key to request from the URL repository.
     *
     * @var string
     */
    protected $urlRepositoryKey = 's.agenda_list.committee_weeks';
}
