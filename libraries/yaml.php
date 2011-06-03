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

class X_yaml
{

    /**
     * Parses yaml file with caching.
     * Uses http://pecl.php.net/yaml if available
     * @param string $file YAML file to be parsed
     * @static
     */
    public static function parse ( $file )
    {
        $file_path = XT_PROJECT_DIR .'/'. $file;
        $cached_path = XT_PROJECT_DIR .'/cache/'. $file;

        // Check if YAML file was updated since the last caching
        if ( !file_exists ( $cached_path ) ||
                filemtime ( $file_path ) > filemtime ( $cached_path ) )
        {
            // Generate a new cache
            $cached_dir = dirname ( $cached_path );
            if ( !is_dir ( $cached_dir ) )
            {
                mkdir ( $cached_dir, 0777, true );
                chmod ( $cached_dir, 0777 );
            }

            // Parse, cache & return
            if ( extension_loaded ( 'yaml' ) )
            {
                $parsed = yaml_parse_file ( $file_path );
            }
            else
            {
                $parsed = yaml::load ( $file_path );
            }
            file_put_contents ( $cached_path, serialize ( $parsed ) );

            // Chmod file if it isn't already world-writeable
            $perms = substr ( decoct ( fileperms ( $cached_path ) ), -3 );
            if ( $perms != 777 ) chmod ( $cached_path, 0777 );
            return $parsed;
        }

        // Return cached file
        return unserialize ( file_get_contents ( $cached_path ) );
    }

}