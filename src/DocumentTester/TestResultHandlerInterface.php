<?php namespace Pandemonium\Methuselah\DocumentTester;

/**
 * Handle test results.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
interface TestResultHandlerInterface
{
    /**
     * Handle a passing test.
     *
     * @param  string  $document
     * @return void
     */
    public function pass($document);

    /**
     * Handle a failing test.
     *
     * @param  string  $document
     * @param  string  $explanation
     * @return void
     */
    public function fail($document, $explanation = null);
}
