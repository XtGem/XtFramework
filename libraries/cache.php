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

class X_cache
{

    private static $backend = null,
                   $backend_config = array (),
                   $backend_object,
                   $backend_initialized = false,
                   $key_index = array (),
                   $key_index_modified = false;

    
    /**
     * Initializes a cache backend
     */
    public static function init ()
    {
        if ( self::$backend_initialized ) return;
        
        $cache_config = X::get ( 'config', 'cache' );
        foreach ( $cache_config as $backend => $config )
        {
            if ( $backend == 'files' )
            {
                self::$backend = 'files';
                self::$backend_config = $config;
                break;
            }
            elseif ( extension_loaded ( $backend ) )
            {
                // Additional check to see if eAccelerator is able to cache variables
                if ( $backend == 'eaccelerator' &&
                        !function_exists ( 'eaccelerator_get' ) ) continue;

                self::$backend = $backend;
                self::$backend_config = $config;
                break;
            }
        }

        register_shutdown_function ( 'X_cache::close' );

        X::set ( 'framework', 'cache_backend', self::$backend );
        call_user_func ( 'X_cache::'. self::$backend .'_init' );
        self::$backend_initialized = true;
    }


    /**
     * Wrapper method for getting data out of cache
     * @param string $key Key to retrieve
     * @return mixed
     * @static
     */
    public static function get ( $key )
    {
        X_cache::init ();
        return call_user_func ( 'X_cache::'. self::$backend .'_get', $key );
    }


    /**
     * Wrapper method for setting cache value
     * @param string $key Key to set
     * @param mixed $value Value to set
     * @param integer $expires Cache lifetime. 0 to disable.
     * @return mixed
     * @static
     */
    public static function set ( $key, $value, $expires = 0 )
    {
        X_cache::init ();
        return call_user_func ( 'X_cache::'. self::$backend .'_set', $key, $value, $expires );
    }


    /**
     * Wrapper method for deleting a cache value
     * @param string $key Key to delete
     */
    public static function delete ( $key )
    {
        X_cache::init ();
        return call_user_func ( 'X_cache::'. self::$backend .'_delete', $key );
    }


    /**
     * Return available cache keys
     * @return array
     */
    public static function keys ()
    {
        X_cache::init ();
        if ( !function_exists ( 'X_cache::'. self::$backend .'_keys' ) ) return array ();
        return call_user_func ( 'X_cache::'. self::$backend .'_keys' );
    }


    /**
     * Clear the entire cache
     */
    public static function clear ()
    {
        X_cache::init ();
        return call_user_func ( 'X_cache::'. self::$backend .'_clear' );
    }


    /**
     * Cleanup function wrapper
     */
    public static function close ()
    {
        if ( !self::$backend_initialized ) return;

        self::$backend_initialized = false;
        call_user_func ( 'X_cache::'. self::$backend .'_close' );
    }


    /**
     * Convenience class for automatically serializing objects
     * @param mixed $value
     */
    private static function serialize ( &$value )
    {
        return;
        if ( is_object ( $value ) )
        {
            $value = array ( 'serialized' => true, 'data' => serialize ( $value ) );
        }
    }


    /**
     * Convenience class for automatically unserializing objects
     * @param mixed $value
     */
    private static function unserialize ( &$value )
    {
        return;
        if ( isset ( $value [ 'serialized' ] ) && isset ( $value [ 'data' ] ) )
        {
            $value = unserialize ( $value [ 'data' ] );
        }
    }



    
    // File cache handling methods
    private static function files_init ()
    {
        // Load key index
        $index = XT_PROJECT_DIR .'/'. self::$backend_config [ 'dir' ] .'/key_index';
        if ( file_exists ( $index ) )
        {
            foreach ( file ( $index ) as $line )
            {
                $expl = explode ( '.', $line, 3 );

                // Remove expired caches
                if ( time () > $expl [ 0 ] )
                {
                    self::files_delete ( $expl [ 2 ] );
                    self::$key_index_modified = true;
                }
                else
                {
                    self::$key_index [ $expl [ 2 ] ] =
                            array ( 'expiration' => $expl [ 0 ],
                                    'serialized' => $expl [ 1 ] );
                }
            }
        }
    }
    private static function files_get ( $key )
    {
        // Cache doesn't exist
        if ( !isset ( self::$key_index [ $key ] ) ) return false;

        $path = self::files_get_path ( $key );
        if ( !file_exists ( $path ) ) return false;

        // Return cached content
        $contents = file_get_contents ( $path );

        // Check if the contents are serialized
        if ( self::$key_index [ $key ] [ 'serialized' ] == '1' )
        {
            $contents = unserialize ( $contents );
        }
        
        return $contents;
    }
    private static function files_set ( $key, $value, $expires )
    {
        // Serialize objects and arrays
        if ( is_array ( $value ) || is_object ( $value ) )
        {
            $value = serialize ( $value );
            $serialized = true;
        }
        else
        {
            $serialized = false;
        }

        $path = self::files_get_path ( $key, true );

        if ( file_put_contents ( $path, $value ) )
        {
            // Update key index
            self::$key_index [ $key ] = array (
                    'expiration'   =>  ( $expires == 0 ? 0 : ( time () + $expires ) ),
                    'serialized'   =>  ( $serialized ? 1 : 0 ),
            );
            self::$key_index_modified = true;
        }
    }
    private static function files_delete ( $key )
    {
        $path = self::files_get_path ( $key );
        if ( file_exists ( $path ) ) unlink ( $path );
    }
    private static function files_keys ()
    {
        return array_keys ( $this -> key_index );
    }
    private static function files_clear ( $dir = null )
    {
        if ( $dir == null ) 
        {
            $dir = XT_PROJECT_DIR .'/'. self::$backend_config [ 'dir' ];
        }

        foreach ( glob ( $dir .'/*' ) as $f )
        {
            if ( is_dir ( $f ) )
            {
                self::files_clear ( $f );
                rmdir ( $f );
            }
            else
            {
                unlink ( $f );
            }
        }
    }
    private static function files_close ()
    {
        // Write key index
        if ( self::$key_index_modified )
        {
            $f = fopen ( XT_PROJECT_DIR .'/'.
                            self::$backend_config [ 'dir' ] .'/key_index', 'w' );
            if ( flock ( $f, LOCK_EX ) )
            {
                foreach ( self::$key_index as $key => $options )
                {
                    fwrite ( $f,
                        $options [ 'expiration' ] .'.'.
                        $options [ 'serialized' ] .'.'.
                        $key );
                }
                flock ( $f, LOCK_UN );
                fclose ( $f );
            }
            else
            {
                throw new error ( 'Could not commit cache key index' );
            }
        }
    }
    private static function files_get_key ( $key )
    {
        return md5 ( $key );
    }
    private static function files_get_path ( $key, $create_dir = false )
    {
        $key = self::files_get_key ( $key );
        $dir = XT_PROJECT_DIR
                .'/'. self::$backend_config [ 'dir' ] .'/'
                . $key [ 0 ] .'/'
                . ( isset ( $key [ 1 ] ) ? $key [ 1 ] : '_' ) .'/'
                . ( isset ( $key [ 2 ] ) ? $key [ 2 ] : '_' );
        if ( $create_dir && !is_dir ( $dir ) ) mkdir ( $dir, 0777, true );
        return $dir .'/'. $key;

    }


    // Memcached handling methods
    private static function memcached_init ()
    {
        self::$backend_object = new Memcached ();

        if ( isset ( self::$backend_config [ 'servers' ] ) )
        {
            $servers = array ();

            foreach ( self::$backend_config [ 'servers' ] as $server )
            {
                $weight = ( isset ( $server [ 'weight' ] ) ? $server [ 'weight' ] : 0 );
                $servers [] = array ( $server [ 'server' ], $server [ 'port' ], $weight );
            }

            self::$backend_object -> addServers ( $servers );
        }
        else
        {
            self::$backend_object -> addServer (
                        self::$backend_config [ 'server' ],
                        self::$backend_config [ 'port' ]
                    );
        }
    }
    private static function memcached_get ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> get ( $key );
    }
    private static function memcached_set ( $key, $value, $expires )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> set ( $key, $value, $expires );
    }
    private static function memcached_delete ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> delete ( $key );
    }
    private static function memcached_clear ()
    {
        self::$backend_object -> flush ();
    }
    private static function memcached_close ()
    {
    }


    // Memcache handling methods
    private static function memcache_init ()
    {
        self::$backend_object = new Memcache ();
        if ( isset ( self::$backend_config [ 'servers' ] ) )
        {
            foreach ( self::$backend_config [ 'servers' ] as $server )
            {
                $weight = ( isset ( $server [ 'weight' ] ) ? $server [ 'weight' ] : 0 );
                self::$backend_object -> addServer ( $server [ 'server' ],
                                                     $server [ 'port' ],
                                                     false,
                                                     $weight );
            }
        }
        else
        {
            self::$backend_object -> addServer (
                        self::$backend_config [ 'server' ],
                        self::$backend_config [ 'port' ]
                    );
        }
    }
    private static function memcache_get ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> get ( $key );
    }
    private static function memcache_set ( $key, $value, $expires )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> set ( $key, $value, $expires );
    }
    private static function memcache_delete ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return self::$backend_object -> delete ( $key );
    }
    private static function memcache_keys ()
    {
        $namespace = preg_quote ( self::$backend_config [ 'namespace' ] );
        $keys = array ();
        $allslabs = self::$backend_object -> getExtendedStats ( 'slabs' );

        foreach ( $allslabs as $slabs )
        {
            foreach ( array_keys ( $slabs ) as $slabid )
            {
                if ( !is_integer ( $slabid ) ) continue;

                $dump = self::$backend_object -> getExtendedStats ( 'cachedump', $slabid );
                foreach ( $dump as $entries )
                {
                    if ( $entries )
                    {
                        foreach ( array_keys ( $entries ) as $key )
                        {
                            if ( preg_match ( '#^'. $namespace .'(.+)$#', $key, $m ) )
                            {
                                $keys [] = $m [ 1 ];
                            }
                        }
                    }
                }
            }
        }

        return $keys;
    }
    private static function memcache_clear ()
    {
        self::$backend_object -> flush ();
    }
    private static function memcache_close ()
    {
        return self::$backend_object -> close ();
    }


    // APC handling methods
    private static function apc_init ()
    {
    }
    private static function apc_get ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        $value = apc_fetch ( $key );
        self::unserialize ( $value );
        return $value;
    }
    private static function apc_set ( $key, $value, $expires )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        self::serialize ( $value );
        return apc_store ( $key, $value, $expires );
    }
    private static function apc_delete ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return apc_delete ( $key );
    }
    private static function apc_clear ()
    {
        apc_clear_cache ( 'user' );
    }
    private static function apc_close ()
    {
    }


    // Xcache handling methods
    private static function xcache_init ()
    {
        if ( PHP_SAPI == 'cli' )
        {
            ini_set ( 'apc.enable_cli', 1 );
        }
    }
    private static function xcache_get ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return xcache_get ( $key );
    }
    private static function xcache_set ( $key, $value, $expires )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return xcache_set ( $key, $value, $expires );
    }
    private static function xcache_delete ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return xcache_unset ( $key );
    }
    private static function xcache_clear ()
    {
        $_SERVER["PHP_AUTH_USER"] = 'mOo';
        $_SERVER["PHP_AUTH_PW"] = '';

        xcache_clear_cache ( XC_TYPE_VAR );
    }
    private static function xcache_close ()
    {
    }


    // eAccelerator handling methods
    private static function eaccelerator_init ()
    {
    }
    private static function eaccelerator_get ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        $value = eaccelerator_get ( $key );
        self::unserialize ( $value );
        return $value;
    }
    private static function eaccelerator_set ( $key, $value, $expires )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        self::serialize ( $value );
        return eaccelerator_put ( $key, $value, $expires );
    }
    private static function eaccelerator_delete ( $key )
    {
        $key = self::$backend_config [ 'namespace' ] . $key;
        return eaccelerator_rm ( $key );
    }
    private static function eaccelerator_clear ()
    {
        throw new error ( "eAccelerator does not support cache-clearing" );
    }
    private static function eaccelerator_close ()
    {
    }
}