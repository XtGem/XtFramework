#!/usr/bin/env php
<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *   The MIT License                                                                 *
 *                                                                                   *
 *   Copyright (c) 2011 Povilas Musteikis, UAB XtGem                                 *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *    The above copyright notice and this permission notice shall be included in     *
 *   all copies or substantial portions of the Software.                             *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN       *
 *   THE SOFTWARE.                                                                   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

if ( !isset ( $argv [ 4 ] ) )
{
    die ( "Usage: file.sql count_inserts table data\n". 
          'e.g. db/fixtures/entries.sql 1000 tablename "field1=\'value1\', field2=\'value\\$@\'"' . "\n".
          '($@ will be replaced into iterator value - some shells may require $ to be escaped)' . "\n" );
}

ini_set ( 'memory_limit', '4G' );
set_time_limit ( 0 );

$output = realpath ( $argv [ 1 ] );
if ( !$output ) die ( "Output file does not exist (create it first)\n" );
$count = $argv [ 2 ];

$contents = null;

for ( $i = 1; $i <= $count; $i++ )
{
    $contents .= 'INSERT INTO '. $argv [ 3 ] .' SET '. str_replace ( '$@', $i, $argv [ 4 ] ) .";\n";
    $progress = floor ( ( $i *100 ) / $count );
    echo "\r". $progress .'%';
}

echo " Writing...";

$res = file_put_contents ( $output, $contents );

echo ( $res ? 'ok!' : 'err' ) ."\n";