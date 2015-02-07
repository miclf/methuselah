<?php namespace Pandemonium\Methuselah\DocumentTester;

use Exception;

/**
 * Exception indicating that two strings mismatch.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class MismatchException extends Exception
{
    /**
     * Message of the exception.
     *
     * @var string
     */
    protected $message = 'Expected result and actual result do not match';

    /**
     * A diff between two strings.
     *
     * @var string
     */
    protected $diff;

    /**
     * Get the diff associated to the exception.
     *
     * @return string
     */
    public function getDiff()
    {
        return $this->diff;
    }

    /**
     * Set the diff associated to the exception.
     *
     * @param string  $diff
     * @return self
     */
    public function setDiff($diff)
    {
        $this->diff = (string) $diff;

        return $this;
    }
}
