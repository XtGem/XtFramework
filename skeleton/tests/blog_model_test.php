<?php

// Initialize the test object
$t = new unit_test ( 2 );

$t -> note ( 'Testing model \'blog\'' );

$model = X::model ( 'blog' );

$t -> is ( $model -> parse_tags ( 'foo,bar,baz,test' ),
           array ( 'foo', 'bar', 'baz', 'test' ),
           'Checking if parse_tags is working correctly (simple)' );

$t -> is ( $model -> parse_tags ( 'foo, bar,baz,,,,     test' ),
           array ( 'foo', 'bar', 'baz', 'test' ),
           'Checking if parse_tags is working correctly (complicated)' );