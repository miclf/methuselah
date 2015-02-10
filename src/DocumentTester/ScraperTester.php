<?php namespace Pandemonium\Methuselah\DocumentTester;

use Exception;
use Pandemonium\Methuselah\DocumentTester\MismatchException;
use Illuminate\Container\Container;
use SebastianBergmann\Diff\Differ;

/**
 * Test scrapers against expected output.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class ScraperTester
{
    /**
     * The scraper under test.
     *
     * @var \Pandemonium\Methuselah\Scrapers\ScraperInterface
     */
    protected $scraper;

    /**
     * A test result handler.
     *
     * @var \Pandemonium\Methuselah\DocumentTester\TestResultHandlerInterface
     */
    protected $testResultHandler;

    /**
     * Root directory where the target JSON files are stored.
     *
     * @var string
     */
    protected $baseJsonDirectory = './tests/DocumentTester/Output/';

    /**
     * Regular expression matching identifiers.
     *
     * @var string
     */
    protected $identifierPattern = '\d+';

    /**
     * Settings defined on the tester.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param  array   $options
     * @return void
     */
    public function __construct(array $options)
    {
        $this->scraper = $this->makeScraper($options['scraper']);

        $this->setOptions($options);
    }

    /**
     * Set options on the tester.
     *
     * @param  array  $options
     * @return void
     */
    protected function setOptions(array $options)
    {
        $this->options = $options;

        $this->testResultHandler = $options['testResultHandler'];

        if (!isset($options['useLiveServer']) || !$options['useLiveServer']) {
            $this->setLocalUrlRepository();
        }

        if (isset($options['identifierPattern'])) {
            $this->identifierPattern = $options['identifierPattern'];
        }
    }

    /**
     * Run a batch of tests.
     *
     * @return void
     */
    public function run()
    {
        array_map([$this, 'runTest'], $this->findTestTargets());
    }

    /**
     * Test a scraper against a given target.
     *
     * @param  array  $target
     * @return void
     */
    protected function runTest($target)
    {
        $documentName = $target['file'];

        if (isset($target['options'])) {
            $this->scraper->setOptions($target['options']);
        }

        try {
            $this->testJson($documentName);

            $this->testResultHandler->pass($documentName);

        } catch (MismatchException $e) {
            $this->testResultHandler->fail($documentName, $e->getDiff());
        }
    }

    /**
     * Check the output of the scraper under test against an
     * expected JSON structure using a given identifier.
     *
     * @param  string  $identifier
     * @return bool
     *
     * @throws \Pandemonium\Methuselah\DocumentTester\MismatchException if the
     *         output of the scraper does not match what’s expected.
     */
    protected function testJson($identifier)
    {
        $result = $this->scraper->scrape();

        $expectedJson = $this->getExpectedJson($identifier);

        if (json_decode($expectedJson, true) !== $result) {
            throw (new MismatchException)->setDiff(
                $this->makeDiff($expectedJson, $result)
            );
        }

        return true;
    }

    /**
     * Get the expected JSON for a given scraper and document.
     *
     * @param  string  $identifier
     * @return string
     *
     * @throws \Exception if no JSON file exists for the given identfier.
     */
    protected function getExpectedJson($identifier)
    {
        $path = $this->targetJsonDirectory().'/'.$identifier.'.json';

        if (!file_exists($path)) {
            throw new Exception("Expected JSON file [$path] does not exist");
        }

        return file_get_contents($path);
    }

    /**
     * Find target files for the test batch.
     *
     * @return array
     */
    protected function findTestTargets()
    {
        if (!empty($this->options['identifiers'])) {
            return $this->targetizeIdentifiers($this->options['identifiers']);
        }

        // If no identifier has been explicitly specified, we
        // will retrieve the full list of available ones.
        return $this->scanForTargets($this->targetJsonDirectory());
    }

    /**
     * Scan a given directory for test target files.
     *
     * @param  string  $directory
     * @return array
     */
    protected function scanForTargets($directory)
    {
        $targets = [];
        $baseOptions = $this->getBaseTargetOptions();

        $usesIdentifiers = $this->scraperUsesIdentifiers();

        foreach (scandir($directory) as $file) {

            if (is_null($document = $this->getFileIdentifier($file))) {
                continue;
            }

            $target = ['file' => $document, 'options' => $baseOptions];

            if ($usesIdentifiers) {
                $target['options']['identifier'] = $document;
            }

            $targets[] = $target;
        }

        return $targets;
    }

    /**
     * Get a list of base options to apply to every test target.
     *
     * @return array
     */
    protected function getBaseTargetOptions()
    {
        $except = ['scraper', 'useLiveServer', 'testResultHandler', 'use-prod'];

        return array_except($this->options, $except);
    }

    /**
     * Set a URL repository to be used to retrieve documents.
     *
     * @return void
     */
    protected function setLocalUrlRepository()
    {
        $this->scraper
            ->getDocumentProvider()
            ->getUrlRepository()
            ->setSource(__DIR__.'/url_repository.json');
    }

    /**
     * Instantiate a scraper of the given class.
     *
     * @param  string  $class
     * @return \Pandemonium\Methuselah\Scrapers\ScraperInterface
     */
    protected function makeScraper($class)
    {
        return (new Container)->make($class);
    }

    /**
     * Check if the scraper under test uses identifiers.
     *
     * @return bool
     */
    protected function scraperUsesIdentifiers()
    {
        return isset($this->options['identifiers']);
    }

    /**
     * Wrap an array of identifiers.
     *
     * @param  array  $identifiers
     * @return array
     */
    protected function targetizeIdentifiers(array $identifiers)
    {
        $baseOptions = $this->getBaseTargetOptions();

        return array_map(function ($identifier) use ($baseOptions) {
            return [
                'file'    => $identifier,
                'options' => $baseOptions + ['identifier' => $identifier]
            ];
        }, $identifiers);
    }

    /**
     * Get the directory of target JSON files for the scraper under test.
     *
     * @return string
     */
    protected function targetJsonDirectory()
    {
        // Break the fully qualified class name of the scraper into pieces.
        $parts = explode('\\', get_class($this->scraper));

        // Skip the irrelevant namespace parts.
        $folders = array_slice($parts, 3);

        // Append the rest to the base path.
        return $this->baseJsonDirectory.implode('/', $folders);
    }

    /**
     * Extract the identifier from a file name.
     *
     * @param  string       $fileName
     * @return string|null
     */
    protected function getFileIdentifier($fileName)
    {
        if (preg_match($this->getFilenamePattern(), $fileName, $matches)) {
            return $matches[1];
        }
    }

    /**
     * Get the pattern for file names of test targets.
     *
     * @return string
     */
    protected function getFilenamePattern()
    {
        if ($this->scraperUsesIdentifiers()) {
            return '#^('.$this->identifierPattern.')\.json$#';
        }

        return '#^(.+)\.json$#';
    }

    /**
     * Generate a diff of two strings.
     *
     * @param  string  $from
     * @param  string  $to
     * @return string
     */
    protected function makeDiff($from, $to)
    {
        $options = JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE;

        if (!is_string($from)) $from = json_encode($from, $options);
        if (!is_string($to))   $to   = json_encode($to, $options);

        return (new Differ(''))->diff($from, $to);
    }
}
