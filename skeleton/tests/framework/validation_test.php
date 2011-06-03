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

// Initialize the test object
$t = new unit_test ( 5 );

$t -> note ( 'Testing X_validation' );

$_SERVER [ 'REQUEST_METHOD' ] = 'POST';
$_POST [ 'author' ] = 'test';
$_POST [ 'message' ] = 'test';
$_POST [ 'submit' ] = '';
$_SERVER [ 'REMOTE_ADDR' ] = '1.2.3.4';
$_POST [ '__token' ] = X_validation::get_xsrf_token ();
$t -> is ( X_validation::validate ( 'guestbook' ), true, 'Testing if validation passes' );

unset ( $_POST [ 'submit' ] );
$t -> is ( X_validation::validate ( 'guestbook' ), false, 'Testing if validation fails due to undefined field' );

$_POST [ 'submit' ] = '';
$_POST [ 'author' ] = '';
$t -> is ( X_validation::validate ( 'guestbook' ), false, 'Testing if validation fails due to invalid data' );

$_POST [ 'author' ] = 'test';
$_POST [ 'foo' ] = 'bar';
X_validation::validate ( 'guestbook' );
$t -> ok ( isset ( $_POST [ 'foo' ] ), 'Testing if validation removes unneeded fields' );

$_SERVER [ 'REQUEST_METHOD' ] = 'DELETE';
$t -> is ( X_validation::validate ( 'guestbook' ), false, 'Testing if validation fails due to incorrect method' );