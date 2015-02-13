<?php namespace Pandemonium\Methuselah\DocumentTester\K;

use Pandemonium\Methuselah\DocumentTester\ScraperTesterCommand;

/**
 * Command to test the K\CommitteeList scraper.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class CommitteeListTester extends ScraperTesterCommand
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $name = 'k:committee-list';
}
