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

$t = new unit_test ( 3 );

X_cache::init ();
$backend = X::get ( 'framework', 'cache_backend' );

$t -> note ( 'Testing X_cache (current backend: '. $backend .')' );

if ( $backend == 'apc' ) 
{
    ini_set ( 'apc.enable_cli', 1 );
    if ( !ini_get ( 'apc.enable_cli' ) )
    {
        $t -> note ( 'Add "apc.enable_cli = 1" to your PHP config file or APC won\'t work in CLI mode' );
    }
}


X_cache::set ( 'test1', 'foobar' );
$t -> is (
            X_cache::get ( 'test1' ),
            'foobar',
            'get/set'
        );


X_cache::delete ( 'test1' );
$t -> is (
            X_cache::get ( 'test1' ),
            false,
            'delete'
        );


X_cache::set ( 'test2', 'foobar' );
X_cache::clear ();
$t -> is (
            X_cache::get ( 'test2' ),
            false,
            'clear'
        );