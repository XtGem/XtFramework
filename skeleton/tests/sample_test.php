<?php

// Stub objects and functions for test purposes
class myObject
{

    public function myMethod ()
    {
        
    }

}

function throw_an_exception ()
{
    throw new Exception ( 'exception thrown' );
}

// Initialize the test object
$t = new unit_test ( 16 );

$t -> note ( 'hello world' );

$t -> ok ( 1 == '1', 'the equal operator ignores type' );
$t -> is ( 1, '1', 'a string is converted to a number for comparison' );
$t -> isnt ( 0, 1, 'zero and one are not equal' );
$t -> like ( 'test01', '/test\d+/', 'test01 follows the test numbering pattern' );
$t -> unlike ( 'tests01', '/test\d+/', 'tests01 does not follow the pattern' );
$t -> cmp ( 1, '<', 2, 'one is inferior to two' );
$t -> cmp ( 1, '!==', true, 'one and true are not identical' );
$t -> is_a ( 'foobar', 'string', '\'foobar\' is a string' );
$t -> is_a ( new myObject (), 'myObject', 'new creates object of the right class' );
$t -> can ( new myObject (), 'myMethod', 'objects of class myObject do have a myMethod method' );

try
{
    throw_an_exception ();
    $t -> ok ( false, 'no code should be executed after throwing an exception' );
}
catch ( Exception $e )
{
    $t -> ok ( true, 'exception caught successfully' );
}

if ( !isset ( $foobar ) )
{
    $t -> skip ( 'skipping one test to keep the test count exact in the condition', 1 );
}
else
{
    $t -> ok ( $foobar, 'foobar' );
}

$array1 = array (
        'foo'   =>  array (
                'bar'   =>  'baz',
            )
    );
$array2 = array (
        'foo'   =>  array (
                'bar'   =>  'baz',
            )
    );
$t -> is ( $array1, $array2, 'Deep array checking' );

$t -> ok ( false, 'this test always fails' )
        or $t -> debug ( 'You can display some information to make debugging easier here' );
$t -> ok ( false, 'this test also always fails' )
        or $t -> debug ( array ( 'foo' => 'bar', 'message' => 'As you can see arrays and objects are also supported' ) );

$t -> todo ( 'one test left to do' );