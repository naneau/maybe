<?php
/**
 * ErrorReturn.php
 *
 * @package         Maybe
 */

namespace Naneau\Maybe;

/**
 * ErrorReturn
 *
 * Internal tracker for handled (failed) calls
 *
 * Takes a user defined error handler (which can generate a value), and
 * provides an error handling method (`ErrorReturn::error()`), that can be used
 * in `set_error_handler()`. When called it uses the user defined handler to
 * generate a value, and keep track of the fact that it was called at all.
 *
 * @category        Naneau
 * @package         Maybe
 */
class ErrorReturn
{
    /**
     * Callable error handler
     *
     * @var callable
     **/
    private $handler;

    /**
     * Actual called tracker
     *
     * @var bool
     **/
    private $called = false;

    /**
     * Returned value
     *
     * @var method
     **/
    private $returnValue;

    /**
     * Constructor
     *
     * @pararm callable $handler
     * @return void
     **/
    public function __construct($handler)
    {
        $this->setHandler($handler);
    }

    /**
     * Error handler
     *
     * @param  int    $number
     * @param  string $message
     * @param  string $file
     * @param  string $line
     * @param  array  $context
     * @return bool
     **/
    public function error($number, $message, $file, $line, $context = array())
    {
        // Fetch overloaded result from user defined error handler
        $return = call_user_func(
            $this->getHandler(),
            $number, $message, $file, $line, $context
        );

        // Set called flag and return value
        $this
            ->setCalled()
            ->setReturnValue($return);

        // boolean return since we're an error handling method
        return true;
    }

    /**
     * Has it been called?
     *
     * @return bool
     */
    public function isCalled()
    {
        return $this->called;
    }

    /**
     * Has it been called?
     *
     * @param  bool        $called
     * @return ErrorReturn
     */
    public function setCalled($called = true)
    {
        $this->called = $called;

        return $this;
    }

    /**
     * Get the returned value
     *
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    /**
     * Set the returned value
     *
     * @param  mixed       $returnValue
     * @return ErrorReturn
     */
    public function setReturnValue($returnValue)
    {
        $this->returnValue = $returnValue;

        return $this;
    }

    /**
     * Get the handler
     *
     * @return callable
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set the handler
     *
     * @param  callable    $handler
     * @return ErrorReturn
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }
}
