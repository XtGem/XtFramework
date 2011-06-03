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

// CLI interface for creating a new framework project

// Do not execute if run via a bootstrap
if ( PHP_SAPI == 'cli' && !defined ( 'XT_PROJECT_DIR' ) )
{
    if ( !isset ( $argv [ 1 ] ) || !isset ( $argv [ 2 ] ) || $argv [ 1 ] != 'create-project' )
        die ( $argv [ 0 ] ." create-project /path/to/new/project\n" );

    $dest = realpath ( $argv [ 2 ] );

    if ( $dest != null )
    {
        // Directory already exists
        if ( !is_dir ( $dest ) )
        {
            die ( "$dest is not a directory\n" );
        }
        else
        {
            $match = glob ( $dest .'/*' );
            if ( !empty ( $match ) )
            {
                if ( !isset ( $argv [ 3 ] ) || $argv [ 3 ] != 'force' )
                {
                    echo "Directory \"$dest\" is not empty\nIf you continue, you risk overwriting existing files.\n";
                    echo "Use 'force' argument to force framework to use this directory:\n";
                    die ( $argv [ 0 ] .' '. $argv [ 1 ] .' '. $argv [ 2 ] ." force\n" );
                }
            }
        }
    }
    else
    {
        $dest = rtrim ( $argv [ 2 ], '/' );
        $res = mkdir ( $dest, 0755, true );
        if ( !$res ) die ( "Could not create $dest directory\n" );
    }

    #compiler include: bin/_skeleton.php

    if ( isset ( $skeleton ) )
    {
        // Run via a self-contained PHP file, create structure from an array

        function create_skeleton ( $dir, $arr )
        {
            foreach ( $arr as $f => $contents )
            {
                echo "+ $dir/$f\n";
                if ( !is_array ( $contents ) )
                {
                    file_put_contents ( $dir .'/'. $f,
                                        $contents );
                }
                else
                {
                    if ( !file_exists ( $dir .'/'. $f ) )
                    {
                        mkdir ( $dir .'/'. $f );
                    }
                    create_skeleton ( $dir, $contents );
                }
            }
        }

        create_skeleton ( $dest, $skeleton );
    }
    else
    {
        // Full framework filesystem structure
        function copy_dir ( $from, $to )
        {
            foreach ( glob ( $from .'/*' ) as $f )
            {
                $dest_f = basename ( $f );
                $dest = $to .'/'. $dest_f;
                echo "+ $dest\n";
                if ( !is_dir ( $f ) )
                {
                    copy ( $f, $dest );
                }
                else
                {
                    if ( !file_exists ( $dest ) )
                    {
                        mkdir ( $dest );
                    }
                    copy_dir ( $f, $dest );
                }
            }
        }

        // Uncompiled framework
        $skeleton_dir = realpath ( dirname ( __FILE__ ) .'/../skeleton' );
        if ( !file_exists ( $skeleton_dir ) )
        {
            // Maybe it's compiled but skeleton isn't included
            $skeleton_dir = dirname ( __FILE__ ) .'/skeleton';
            if ( !file_exists ( $skeleton_dir ) )
            {
                throw new error ( 'Cannot create project: Skeleton dir is not available' );
            }
        }
        copy_dir ( $skeleton_dir, $dest );
    }

    // Set up copied files
    chmod ( $dest .'/cache', 0777 );
    chmod ( $dest .'/cli', 0755 );
    chmod ( $dest .'/bin/generate_fixtures.php', 0755 );
    rename ( $dest .'/www/htaccess', $dest .'/www/.htaccess' );

    // Modify files to contain proper default values
    $project_name = basename ( $dest );
    // config/global.yml
    $content = file_get_contents ( $dest .'/config/global.yml' );
    $content = str_replace ( 'namespace: XtFramework_',
                             'namespace: '. $project_name .'_',
                             $content );
    file_put_contents ( $dest .'/config/global.yml', $content );
    // config/validation.yml
    $content = file_get_contents ( $dest .'/config/validation.yml' );
    $content = str_replace ( 'xsrf_protection: changeme_plz', 
                             'xsrf_protection: '. md5 ( $project_name . uniqid () ) .
                                    rand ( 100, 999 ),
                             $content );
    file_put_contents ( $dest .'/config/validation.yml', $content );
    // bootstrap.php
    $loader_path = ( isset ( $skeleton ) ? __FILE__ : realpath ( dirname ( __FILE__ ) .'/../loader.php' ) );
    $content = file_get_contents ( $dest .'/bootstrap.php' );
    $content = str_replace ( 'include ( \'XtFramework/loader.php\' );', 'include ( \''. $loader_path .'\' );', $content );
    file_put_contents ( $dest .'/bootstrap.php', $content );

    die ();
}