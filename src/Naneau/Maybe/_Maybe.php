<?php
/**
 * Maybe.php
 *
 * Functional style maybe() library, provides a simple wrapper around the OOp
 * structure, that is much more useable
 *
 * @package         Maybe
 * @subpackage      Maybe
 */

namespace Naneau\Maybe;

/**
 * Call any callable, but suppressing error
 *
 * @param callable $generator
 * @param mixed $param1,... parameters for $callable (optional)
 * @param callable $errorHandler
 * @return mixed return value from original function or error handler
 **/
function maybe($generator)
{
    // Arguments
    $args = func_get_args();

    if (count($args) < 2) {
        throw new \InvalidArgumentException(
            'Both a generator and an error handler need to be specified'
        );
    }

    // Retrieve generator and errorHandler, leaving additional arguments
    $generator = array_shift($args);
    $errorHandler = array_pop($args);

    // Maybe
    $maybe = new Maybe($generator, $errorHandler);

    // Call with left-over arguments
    return $maybe->call($args);
}
