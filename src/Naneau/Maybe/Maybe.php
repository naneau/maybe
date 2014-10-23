<?php
/**
 * Maybe.php
 *
 * @package         Maybe
 * @subpackage      Maybe
 */

namespace Naneau\Maybe;

use \InvalidArgumentException;

/**
 * Maybe
 *
 * "Maybe" return using error suppression.
 *
 * Takes two callables, a "generator" and an error handling method. The
 * generator is called in suppressed (@) form, with a set of (optional)
 * parameters. Before calling a temporary error handler is set, which will use
 * the user defined method to generate a value in case the method generates an
 * error.
 *
 * @see Naneau\Maybe\ErrorReturn
 *
 * @category        Naneau
 * @package         Maybe
 * @subpackage      Maybe
 */
class Maybe
{
    /**
     * the value generator
     *
     * @var callable
     */
    private $generator;

    /**
     * the error handling method
     *
     * @var callable
     */
    private $errorHandler;

    /**
     * Constructor
     *
     * @param  callable $generator
     * @param  callable $errorHandler
     * @return void
     **/
    public function __construct($generator, $errorHandler)
    {
        $this
            ->setGenerator($generator)
            ->setErrorHandler($errorHandler);
    }

    /**
     * Call the generator with a set of arguments
     *
     * The actual "maybe" call
     *
     * @param  mixed[] $arguments
     * @return mixed
     **/
    public function call($arguments = array())
    {
        // Error handler tracker
        $errorResult = new ErrorReturn($this->getErrorHandler());

        // Set temporary error handler
        set_error_handler(array($errorResult, 'error'));

        // Call method, suppressed, may return valid $return, may not
        $return = @call_user_func_array($this->getGenerator(), $arguments);

        // Restore original error handler
        restore_error_handler();

        if ($errorResult->isCalled()) {
            return $errorResult->getReturnValue();
        }

        return $return;
    }

    /**
     * Get the value generator
     *
     * @return callable
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the value generator
     *
     * @param  callable $generator
     * @return Maybe
     */
    public function setGenerator($generator)
    {
        if (!is_callable($generator)) {
            throw new InvalidArgumentException(
                'Invalid generator given, needs to be callable'
            );
        }

        $this->generator = $generator;

        return $this;
    }

    /**
     * Get the error handling method
     *
     * @return callable
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * Set the error handling method
     *
     * @param  callable $errorHandler
     * @return Maybe
     */
    public function setErrorHandler($errorHandler)
    {
        if (!is_callable($errorHandler)) {
            throw new InvalidArgumentException(
                'Invalid error handler given, needs to be callable'
            );
        }

        $this->errorHandler = $errorHandler;

        return $this;
    }
}
