<?php

use \PHPUnit_Framework_TestCase as TestCase;
use \Exception;

class MaybeTest extends TestCase
{
    /**
     * Test succesful call
     *
     * @return void
     **/
    public function testSuccess() {
        $serialized = serialize('foo');

        $unserialized = Naneau\Maybe\maybe(
            'unserialize',
            $serialized,
            function() {
                throw new Exception();
            }
        );

        $this->assertEquals('foo', $unserialized);
    }

    /**
     * Test error handler return value
     *
     * @return void
     **/
    public function testFailReturnValue() {
        $serialized = 'foo';

        $unserialized = Naneau\Maybe\maybe(
            'unserialize',
            $serialized,
            function() {
                return 123;
            }
        );

        $this->assertEquals(123, $unserialized);
    }

    /**
     * Test error handler return value
     *
     * @return void
     **/
    public function testFailCall() {
        $serialized = 'foo';

        $test = $this;
        $unserialized = Naneau\Maybe\maybe(
            'unserialize',
            $serialized,
            function($number, $message) use ($test) {
                // check number/message
                $test->assertEquals(8, $number);
                $test->assertEquals('unserialize(): Error at offset 0 of 3 bytes', $message);

                return 123;
            }
        );

        $this->assertEquals(123, $unserialized);
    }

    /**
     * Test double closure style
     *
     * @return void
     **/
    public function testClosure() {
        $serialized = serialize('foo');

        $unserialized = Naneau\Maybe\maybe(
            function() use ($serialized) {
                return unserialize($serialized);
            },
            function() {
                throw new Exception();
            }
        );

        $this->assertEquals('foo', $unserialized);
    }

    /**
     * Test double closure style
     *
     * @return void
     **/
    public function testClosureFailureReturnValue() {
        $serialized = 'bar';

        $unserialized = Naneau\Maybe\maybe(
            function() use ($serialized) {
                return unserialize($serialized);
            },
            function() {
                return 'foo';
            }
        );

        $this->assertEquals('foo', $unserialized);
    }

    /**
     * Test invalid generator
     *
     * @expectedException InvalidArgumentException
     **/
    public function testInvalidGenerator() {
        Naneau\Maybe\maybe(
            'foo',
            function() { return false; }
        );
    }

    /**
     * Test invalid error handler
     *
     * @expectedException InvalidArgumentException
     **/
    public function testInvalidHandler() {
        Naneau\Maybe\maybe(
            function() { return 'foo'; },
            'foo'
        );
    }

    /**
     * Test no error handler
     *
     * @expectedException InvalidArgumentException
     **/
    public function testNoHandler() {
        Naneau\Maybe\maybe(
            function() { return 'foo'; }
        );
    }
}
