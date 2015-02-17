<?php namespace Pandemonium\Methuselah\DocumentTester\K;

use Pandemonium\Methuselah\DocumentTester\ScraperTesterCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command to test the K\MP scraper.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MPTester extends ScraperTesterCommand
{
    /**
     * Name of the command.
     *
     * @var string
     */
    protected $name = 'k:mp';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'identifier',
                InputArgument::IS_ARRAY,
                'The Chamber identifier of the MP',
            ],
        ];
    }
}
