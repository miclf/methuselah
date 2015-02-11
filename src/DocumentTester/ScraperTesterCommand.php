<?php namespace Pandemonium\Methuselah\DocumentTester;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract command to test scrapers.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
abstract class ScraperTesterCommand extends Command implements TestResultHandlerInterface
{
    /**
     * The scraper tester.
     *
     * @var \Pandemonium\Methuselah\DocumentTester\ScraperTester
     */
    protected $tester;

    /**
     * Counter of passed tests.
     *
     * @var int
     */
    protected $passed = 0;

    /**
     * Counters of failed tests.
     *
     * @var int
     */
    protected $failed = 0;

    /**
     * Execute the command.
     *
     * @return void
     */
    public function fire()
    {
        $this->printIntroMessage();

        $this->makeTester()->run();

        $this->printReport();

        exit(0);
    }

    /**
     * Display an introduction to the terminal.
     *
     * @return void
     */
    protected function printIntroMessage()
    {
        $this->info("Testing <comment>".$this->getTestedClassName()."</comment>\n");
    }

    /**
     * Print a report of the test batch.
     *
     * @return void
     */
    protected function printReport()
    {
        $total = $this->passed + $this->failed;

        $report = sprintf("\n%d/%d tests passed", $this->passed, $total);

        $this->line($report);
    }

    /**
     * Instantiate a tester for the scraper under test.
     *
     * @return \Pandemonium\Methuselah\DocumentTester\ScraperTester
     */
    protected function makeTester()
    {
        $options = [
            'scraper'           => $this->getTestedClassName(),
            'useLiveServer'     => $this->option('use-prod'),
            'testResultHandler' => $this,
        ];

        $options += $this->getScraperOptions();

        if ($this->hasIdentifierArgument()) {
            $options['identifiers'] = $this->argument('identifier');
        }

        return $this->tester = (new Container)->make(
            'Pandemonium\Methuselah\DocumentTester\ScraperTester',
            compact('options')
        );
    }

    /**
     * Handle a passing test.
     *
     * @param  string  $document
     * @return void
     */
    public function pass($document)
    {
        $this->outputNotice($document, 'OK', 'info');

        $this->passed++;
    }

    /**
     * Handle a failing test.
     *
     * @param  string  $document
     * @param  string  $explanation
     * @return void
     */
    public function fail($document, $explanation = null)
    {
        $this->outputNotice($document, 'fail', 'error');

        if (!is_null($explanation)) {
            $this->line($explanation);
        }

        $this->failed++;
    }

    /**
     * Output a notice to the terminal.
     *
     * @param  string  $identifier
     * @param  string  $message
     * @return void
     */
    protected function outputNotice($identifier, $message, $type = null)
    {
        $identifier = str_pad($identifier, 10);

        if ($type) {
            $message = "<{$type}>{$message}</{$type}>";
        }

        $this->line($identifier.' '.$message);
    }

    /**
     * Get the fully qualified class name of the scraper under test.
     *
     * @return string
     */
    protected function getTestedClassName()
    {
        list($assembly, $class) = explode(':', $this->name);

        $assembly = strtoupper($assembly);
        $class    = studly_case($class);

        return "Pandemonium\\Methuselah\\Scrapers\\{$assembly}\\{$class}";
    }

    /**
     * Check if the scraper under test requires an identifier.
     *
     * @return bool
     */
    protected function hasIdentifierArgument()
    {
        foreach ($this->getArguments() as $argumentDefinition) {
            if ($argumentDefinition[0] === 'identifier') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the options to pass to the scraper.
     *
     * @return array
     */
    protected function getScraperOptions()
    {
        $options = [];

        foreach ($this->getOptions() as $option) {

            $key   = $option[0];
            $value = $this->option($key);

            $options[$key] = $value;
        }

        return $options;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'use-prod',
                null,
                InputOption::VALUE_NONE,
                'If set, the tester will grab live, online data instead of local documents',
            ],
        ];
    }
}
