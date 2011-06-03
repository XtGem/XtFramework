#!/usr/bin/env php
<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *   The MIT License                                                                 *
 *                                                                                   *
 *   Copyright (c) 2011 Povilas Musteikis, UAB XtGem, Bong Cosca                     *
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

if ( PHP_SAPI != 'cli' ) die ( "Run compile.php via a command line\n" );
if ( !isset ( $argv [ 1 ] ) || !isset ( $argv [ 2 ] ) )
    die ( $argv [ 0 ] ." /path/to/XtFramework/loader.php /output/folder/ [no-skeleton] [with-plugins]\n" );

$loader = realpath ( $argv [ 1 ] );
$output = realpath ( $argv [ 2 ] );
if ( $output == null ) $output = $argv [ 2 ];

$skeleton_dest = dirname(__FILE__).'/_skeleton.php';

if ( !file_exists ( $output ) )
{
    mkdir ( $output, 0777, true )
        or die ( "Could not create $output" );
}
elseif ( !is_dir ( $output ) )
{
    die ( "$output is not a directory\n" );
}
elseif ( !is_writeable ( $output ) )
{
    die ( "$output is not writeable\n" );
}
elseif ( !is_writeable ( $skeleton_dest ) )
{
    die ( "$skeleton_dest is not writeable\n" );
}

$args = array_flip ( array_slice ( $argv, 3 ) );

$compile_skeleton = !isset ( $args [ 'no-skeleton' ] );

//// Compile a skeleton array
// Make sure bin/_skeleton.php is write-able
if ( $compile_skeleton )
{
    $extensions = array ( 'php', 'yml', 'sql', 'txt', 'htaccess', 'schema', 'cli' );
    $skeleton_dir = dirname ( $loader ) .'/skeleton';

    function scan ( $dir )
    {
        global $extensions, $skeleton_dir;
        echo "\tScanning dir: ". $dir ."\n";

        $str = "array (";

        foreach ( glob ( $dir .'/*' ) as $f )
        {
            $key = substr ( $f, strlen ( $skeleton_dir ) +1 );

            if ( !is_dir ( $f ) )
            {
                $ext = strrpos ( $f, '.' );
                if ( $ext === false )
                {
                    $ext = basename ( $f );
                }
                else
                {
                    $ext = substr ( $f, $ext +1 );
                }
                if ( !in_array ( $ext, $extensions ) )
                {
                    echo "\t\tOmitting: $key\n";
                    continue;
                }

                $str .= "\n\"". $key .'" =>'.
                        '"'. str_replace ( "\n", '\n',
                             str_replace ( "\t", '\t',
                             str_replace ( '"', "\\\"",
                             str_replace ( '$', '\$',
                                     file_get_contents ( $f )
                                     ) ) ) ) ."\",";
            }
            else
            {
                $str .= "\n\"". $key .'" =>'. scan ( $f ) .",";
            }
        }

        $str .= ")";

        return $str;
    }

    echo "Generating a skeleton array:\n";
    file_put_contents ( $skeleton_dest,
                        '<?php $skeleton = '. scan ( $skeleton_dir ) .';' );
}
//////

function recursive_read ( $file )
{
    global $loader, $compile_skeleton;
    return preg_replace_callback (
            '/#compiler include: ([^\n]+)/',
            function( $match ) use($loader, $compile_skeleton)
            {
                if ( !$compile_skeleton && $match[ 1 ] == 'bin/_skeleton.php' )
                    return '';
                echo "\tCompiling: $match[1]\n";
                return recursive_read (
                        realpath ( dirname ( $loader ) . '/' . $match[ 1 ] )
                );
            },
            preg_replace (
                    '/^<\?(php)*|(\?>)*\s*$/', '', file_get_contents ( $file )
            )
    );
}

function compile ( $file )
{
    echo "\tCompiling: " . basename ( $file ) . "\n";
    $src = recursive_read ( $file );
    $src = preg_replace (
                    '/#compiler skip start.+?#compiler skip end/s', '', $src
    );
    $dst = '';
    $ptr = 0;
    while ( $ptr < strlen ( $src ) )
    {
        if ( $src[ $ptr ] == '/' )
        {
            // Presume it's a regex pattern
            $regex = TRUE;
            if ( $ptr > 0 )
            {
                // Backtrack and validate
                $ofs = $ptr;
                while ( $ofs > 0 )
                {
                    $ofs--;
                    // Pattern should be preceded by parenthesis,
                    // colon or assignment operator
                    if ( $src[ $ofs ] == '(' || $src[ $ofs ] == ':' ||
                            $src[ $ofs ] == '=' )
                    {
                        while ( $ptr < strlen ( $src ) )
                        {
                            $str = strstr ( substr ( $src, $ptr + 1 ), '/', TRUE );
                            if ( !strlen ( $str ) && $src[ $ptr - 1 ] != '/' ||
                                    strpos ( $str, "\n" ) !== FALSE )
                            {
                                // Not a regex pattern
                                $regex = FALSE;
                                break;
                            }
                            $dst.='/' . $str;
                            $ptr+=strlen ( $str ) + 1;
                            if ( $src[ $ptr - 1 ] != '\\' || $src[ $ptr - 2 ] == '\\' )
                            {
                                $dst.='/';
                                $ptr++;
                                break;
                            }
                        }
                        break;
                    }
                    elseif ( $src[ $ofs ] != "\t" && $src[ $ofs ] != ' ' )
                    {
                        // Not a regex pattern
                        $regex = FALSE;
                        break;
                    }
                }
                if ( $regex && _ofs < 1 )
                    $regex = FALSE;
            }
            if ( !$regex || $ptr < 1 )
            {
                if ( $src[ $ptr + 1 ] == '*' )
                {
                    // Multiline comment
                    $str = strstr ( substr ( $src, $ptr + 2 ), '*/', TRUE );
                    $ptr+=strlen ( $str ) + 4;
                }
                elseif ( $src[ $ptr + 1 ] == '/' )
                {
                    // Multiline comment
                    $str = strstr ( substr ( $src, $ptr + 2 ), "\n", TRUE );
                    $ptr+=strlen ( $str ) + 2;
                }
                else
                {
                    // Division operator
                    $dst.=$src[ $ptr ];
                    $ptr++;
                }
            }
            continue;
        }
        if ( $src[ $ptr ] == '\'' || $src[ $ptr ] == '"' )
        {
            $match = $src[ $ptr ];
            // String literal
            while ( $ptr < strlen ( $src ) )
            {
                $str = strstr ( substr ( $src, $ptr + 1 ), $src[ $ptr ], TRUE );
                $dst.=$match . $str;
                $ptr+=strlen ( $str ) + 1;
                if ( $src[ $ptr - 1 ] != '\\' || $src[ $ptr - 2 ] == '\\' )
                {
                    $dst.=$match;
                    $ptr++;
                    break;
                }
            }
            continue;
        }
        if ( preg_match ( '/\s/', $src[ $ptr ] ) )
        {
            $last = substr ( $dst, -1 );
            $ofs = $ptr + 1;
            while ( preg_match ( '/\s/', $src[ $ofs ] ) )
                $ofs++;
            if ( $last && preg_match ( '/[\w\$][\w\$\.]/', $last . $src[ $ofs ] ) )
                $dst.=$src[ $ptr ];
            $ptr = $ofs;
        }
        elseif ( preg_match ( '/^<<<([^\n]+)(.+)(\1);\n/s',
                        substr ( $src, $ptr ), $match ) )
        {
            // HereDoc block
            $dst.=$match[ 0 ];
            $ptr+=strlen ( $match[ 0 ] );
        }
        else
        {
            $dst.=$src[ $ptr ];
            $ptr++;
        }
    }

    return $dst;
}

$contents = null;

// Compile plugins
if ( isset ( $args [ 'with-plugins' ] ) )
{
    $blacklist = array ( 'xt_doctrine_connection.php' );

    echo "Compiling-in plugins:\n";
    foreach ( glob ( dirname ( $loader ) .'/plugins/*.php' ) as $plugin )
    {
        $plugin_name = basename ( $plugin );
        if ( in_array ( $plugin_name, $blacklist ) )
        {
            echo "\tSkipping: ". $plugin_name ."\n";
            continue;
        }

        $contents .= compile ( $plugin );
    }
}

echo "Compiling framework into a single self-contained file:\n";
$contents .= compile ( $loader );

$contents = '<?' . 'php' . "\n". $contents;

file_put_contents ( $output .'/XtFramework.php', $contents );