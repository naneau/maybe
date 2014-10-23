# Maybe

Maybe you want to call functions in PHP. Maybe those functions will slap you
with warnings and errors. Maybe you have no way of checking whether your
arguments are valid before calling. Maybe you want to handle anticipated errors
locally.

Introducing `maybe()`:

```php
<?php

use Naneau\Maybe\maybe;

// Open file handle, maybe.
$handle = maybe('fopen', 'foo.txt', 'r', function($err, $message) {
    throw new RuntimeException('Could not open foo.txt for reading');
});

// Do something useful
$line = fgets($handle);
```

## Introduction

Error handling in PHP is not the easiest of tasks. There are various [flaws and
shortcomings](http://eev.ee/blog/2012/04/09/php-a-fractal-of-bad-design/#error-handling).
What is worse is that certain native functions will throw warnings and errors
without a way to assure they don't. In some cases you can check arguments for
validity cheaply, for instance using `isset()` or `file_exists()`, before
calling a function. Other times you can not, especially when accessing external
resources such as a database or the file system.

You can "suppress" errors, by prefixing a function call with `@`, but this
introduces other problems.

 * In php a function can bubble an error, but also return a value. This value
   may or may not be what you expect (i.e. `false`), which requires additional
   checking.
 * You will need to check the result of any suppressed function see if it is of
   an expected/valid type
 * You have no way of seeing *what* went wrong, if something did, since the
   error message will leave local scope to the error handler, [if there is
   one](http://php.net/manual/en/function.set-error-handler.php)
 * You can somewhat get around this by making your error handler throw
   exceptions for all errors, but this may introduce problems elsewhere

Maybe exposes a single method `maybe()`, that takes a function (callable),
referred to as the "generator" that you fear may throw errors your way, and a
temporary, local error handler. This error handler gets called if (and only if)
a warning or error was triggered, allowing you to do something meaningful right
there and then. After operation, the regular error handler is restored.

## Usage

### Basic Usage

In its most basic form, you can call `maybe()` with two params, a callable
"generator", and a fallback in case of failure:

```php
<?php

// Try to do something
$result = Naneau\Maybe\maybe('someFailingFunction', function() {
    return false;
});

// Result will now either be whatever someFailingFunction() returns, or false
doSomethingUseful($result);
```

### Halting Flow

You could use the error handler to halt flow by throwing an exception, allowing
for local, fine-grained control:
```php
<?php

// Try to do something
$result = Naneau\Maybe\maybe('someFailingFunction', function($errNo, $message) {
    throw new RuntimeException('Failure: ' . $message);
});
```

### Parameters

Any additional parameters to your (callable) generator simply follow it:

```php
<?php

// Try to do something
$fileHandle = Naneau\Maybe\maybe('fopen', 'foo.txt', 'r', function($errNo, $message) {
    throw new RuntimeException('Could not open foo.txt: ' . $message);
});
```

### Functional Notation

As the generator can be any [PHP
callable](http://php.net/manual/en/language.types.callable.php), it can be a
[closure](http://php.net/manual/en/class.closure.php). This allows for a more
functional notation:

```php
<?php

$fileHandle = Naneau\Maybe\maybe(
    function() {
        return fopen('foo.txt', 'r');
    },
    function() {
        throw new RuntimeException('Can not open foo.txt, massive failure!');
    }
);
```

## Logging

A basic use case is the implementation of a logging call when a call fails:

```php
<?php

// Assuming $logger is a PSR log instance (https://github.com/php-fig/log)
$logger = ...;

// Some serialized data (which, when corrupt, will result in a hard to debug error)
$someSerializedData = ...;

// Unserialized data
$unserializedData = Naneau\Maybe\maybe(
    function() use ($someSerializedData) {
        return deserialize($someSerializedData);
    },
    function($errNo, $message) use ($logger, $someSerializedData) {
        $logger->debug(sprintf(
            'Could not deserialize "%s": %s',
            $someSerializedData,
            $message
        ));
        return false;
    }
);

if ($unserializedData !== false) {
    doSomethingUseful($unserializedData);
}
```

## But... But... `@` is evil!

Yes, yes it is. It is a symbol of powerless-ness, and signals deeper issues
with PHP. Yet, `maybe()` relies on it to suppress any warnings and errors
occurring in the generator. This is scary, I understand.

Imagine you need to call a function which may throw errors and warnings:

You use `@`:

 * You lose the error message/code from local scope
 * You can only rely on the return value of the function to check for validity

You don't use `@`:

 * Any errors/warnings leave local scope and go to your error handler (if
   you've set one)
 * You *still* have no real way of checking the error message/code locally

Consider:

 * PHP offers no way to stop functions from bubbling errors/warnings other
   than `@`, and many built-in functions can result in errors/warnings.
 * It is not always possible to check function parameters for validity
 * Even when it is possible to check for parameter validity, it may be
   prohibitively expensive/slow to do so
 * There may be a small performance overhead to using `@`, since the generation
   of an error isn't free, but if the error/warning occurs you'll incur that
   penalty anyway. This holds true for any
   [expression](http://php.net/manual/en/language.expressions.php) where you
   can not cheaply check whether or not it will fail.
 * Setting a global custom error handler won't help
    * Error numbers/messages will move outside of local scope
    * A custom error handler can not determine "seriousness"
    * Throwing exceptions in a custom handler your introduces additional
      performance overhead on top of that which the error handling itself
      generated

### However

In many situations checking expressions for validity before they are executed
*is* cheap. When you can simply use something like `isset()`, do *not* use
`maybe()` or `@`:

```php
<?php

// *Do not do this*
@$foo = $bar['foo'];

// When you really want to do
if (isset($bar['foo'])) {
    $foo = $bar['foo'];
} else {
    // ...
}
```

### Still Not Convinced?

Try the following:

```php
<?php

$count = 100000;

// Ignore errors:
set_error_handler(function() {});

$start = microtime(true);
for ($x = 0; $x < $count; $x++) {
    unserialize('foo');
}
$end = microtime(true) - $start;

echo sprintf("Ignoring errors: %f\n", $end);
restore_error_handler();

// Turn errors into exceptions:
set_error_handler(function() {
    throw new \Exception;
});

$start = microtime(true);
for ($x = 0; $x < $count; $x++) {
    try {
        unserialize('foo');
    } catch (Exception $e) {}
}
$end = microtime(true) - $start;
echo sprintf("Turning errors into exceptions: %f\n", $end);
restore_error_handler();

$start = microtime(true);
for ($x = 0; $x < $count; $x++) {
    @unserialize('foo');
}
$end = microtime(true) - $start;
echo sprintf("Using @: %f\n", $end);
```

