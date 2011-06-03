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

error_reporting ( E_ALL );

if ( ini_get ( 'register_globals' ) )
{
    // From http://php.net/manual/en/faq.misc.php#faq.misc.registerglobals
    if ( isset ( $_REQUEST [ 'GLOBALS' ] ) || isset ( $_FILES [ 'GLOBALS' ] ) )
    {
        die ( 'GLOBALS overwrite attempt detected' );
    }

    $whitelist = array ( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES' );

    $input = array_merge ( $_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, ( isset ( $_SESSION ) && is_array ( $_SESSION ) ? $_SESSION : array ( ) ) );

    foreach ( $input as $k => $v )
    {
        if ( !in_array ( $k, $whitelist ) && isset ( $GLOBALS [ $k ] ) )
        {
            unset ( $GLOBALS [ $k ] );
        }
    }
}


// From http://www.idontplaydarts.com/2010/07/mongodb-is-vulnerable-to-sql-injection-in-php-at-least/
function MongoSanitize ( $var )
{
    if ( is_array ( $var ) )
    {
        $new_var = array ();
        foreach ( $var as $key => $value )
        {
            $new_key = $key;

            if ( is_string ( $key ) ) 
            {
                $new_key = str_replace ( array ( chr ( 0 ), '$' ), null, $key );
            }

            if ( is_array ( $value ) )
            {
                $new_var [ $new_key ] = MongoSanitize ( $value );
            }
            else
            {
                $new_var [ $new_key ] = $value;
            }
        }
        return $new_var;
    }
    else
    {
        $res = MongoSanitize ( array ( $var ) );
        return $res [ 0 ];
    }
    return $var;
}
$_POST = MongoSanitize ( $_POST );
$_GET = MongoSanitize ( $_GET );
$_COOKIE = MongoSanitize ( $_COOKIE );


// Get rid of magic_quotes
ini_set ( 'magic_quotes_runtime', 0 );
if ( get_magic_quotes_gpc () )
{
    function array_map_r ( $cback, $arr  )
    {
        $res = array ();

        foreach ( $arr as $k => $v )
        {
            $res [ $k ] = ( is_array ( $v ) ? array_map_r ( $cback, $v ) : $cback ( $v ) );
        }

        return $res;
    }

    $_GET = array_map_r ( 'stripslashes', $_GET );
    $_POST = array_map_r ( 'stripslashes', $_POST );
    $_COOKIE = array_map_r ( 'stripslashes', $_COOKIE );
}

// Modify REQUEST_METHOD if needed to support PUT and DELETE
if ( isset ( $_POST [ '__method' ] ) &&
     in_array ( $_POST [ '__method' ], array ( 'PUT', 'DELETE' ) ) )
{
    $_SERVER [ 'REQUEST_METHOD' ] = $_POST [ '__method' ];
}

// Set include_path to contain application and project plugins directories
 set_include_path (
            XT_FRAMEWORK_DIR .'/plugins' . PATH_SEPARATOR .
            XT_PROJECT_DIR .'/plugins' . PATH_SEPARATOR .
            get_include_path() );