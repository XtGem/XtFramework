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

/**
 * @todo single-request session variables
 */

class session implements Iterator
{
    private $namespace = null;


    public function __construct ( $namespace = null, $forced_session_id = null )
    {
        $this -> namespace = $namespace;

        // Already initialized
        if ( X::is_set ( 'framework', 'session_backend' ) ) return;

        $session_config = X::get ( 'config', 'session' );
        
        foreach ( $session_config [ 'backends' ] as $backend => $config )
        {
            if ( $backend == 'files' )
            {
                $backend = 'files';
                $backend_config = $config;
                break;
            }
            elseif ( extension_loaded ( $backend ) )
            {
                $backend = $backend;
                $backend_config = $config;
                break;
            }
        }

        // Initialize backend
        X::set ( 'framework', 'session_backend', $backend );
        call_user_func ( array ( $this, $backend .'_init' ), $backend_config );

        // Set lifetime and save path
        ini_set ( 'session.gc_maxlifetime', $session_config [ 'lifetime' ] );

        // Do not pass session id through cookies automatically
        ini_set ( 'session.use_cookies', false );

        // Determine if session id should be passed as GET argument via URL
        // Also, get session id
        if ( $session_config [ 'transport' ] == 'auto' )
        {
            if ( isset ( $_COOKIE [ $session_config [ 'cookie' ] ] ) )
            {
                $session_id = $_COOKIE [ $session_config [ 'cookie' ] ];
                X::set ( 'framework', 'session_transport', 'cookie' );
            }
            else
            {
                $session_id = ( isset ( $_GET [ $session_config [ 'url_get' ] ] ) ?
                            $_GET [ $session_config [ 'url_get' ] ] : null );
                X::set ( 'framework', 'session_transport', 'url' );
            }
        }
        elseif ( $session_config [ 'transport' ] == 'url' )
        {
            $session_id = ( isset ( $_GET [ $session_config [ 'url_get' ] ] ) ?
                            $_GET [ $session_config [ 'url_get' ] ] : null );
            X::set ( 'framework', 'session_transport', 'url' );
        }
        elseif ( $session_config [ 'transport' ] == 'cookie' )
        {
            $session_id = ( isset ( $_COOKIE [ $session_config [ 'cookie' ] ] ) ?
                            $_COOKIE [ $session_config [ 'cookie' ] ] : null );
            X::set ( 'framework', 'session_transport', 'cookie' );
        }

        if ( $forced_session_id != null ) $session_id = $forced_session_id;

        if ( $session_id != null && preg_match ( '#^[a-zA-Z0-9,_]+$#', $session_id ) )
        {
            session_id ( $session_id );
        }
        session_start ();
        $session_id = session_id ();

        // Set the cookie
        if ( $session_config [ 'transport' ] != 'url' )
        {
            setcookie ( $session_config [ 'cookie' ], 
                        $session_id,
                        time () + $session_config [ 'lifetime' ],
                        '/',
                        $session_config [ 'domain' ] );
        }

        X::set ( 'framework', 'session_id', $session_id );

        // Assign namespace variable if it isn't set already
        if ( !isset ( $_SESSION [ $this -> namespace ] ) )
        {
            $_SESSION [ $this -> namespace ] = array ();
        }
    }


    public function destroy ()
    {
        if ( $this -> namespace == null )
        {
            if ( session_destroy () )
            {
                X::un_set ( 'framework', 'session_backend' );
            }
        }
        else
        {
            unset ( $_SESSION [ $this -> namespace ] );
        }
    }


    private function memcached_init ( $config )
    {
        if ( isset ( $config [ 'servers' ] ) )
        {
            $session_save_path = array ();
            foreach ( $config [ 'servers' ] as $server )
            {
                $session_save_path [] = $server [ 'server' ] .':'. $server [ 'port' ];
            }
            $session_save_path = implode ( ',', $session_save_path );
        }
        else
        {
            $session_save_path = $config [ 'server' ] .':'. $config [ 'port' ];
        }

        ini_set ( 'session.save_handler', 'memcached' );
        ini_set ( 'session.save_path', $session_save_path );
    }
    
    private function memcache_init ( $config )
    {
        if ( isset ( $config [ 'servers' ] ) )
        {
            $session_save_path = array ();
            foreach ( $config [ 'servers' ] as $server )
            {
                $session_save_path [] = 'tcp://'. $server [ 'server' ] .':'. $server [ 'port' ];
            }
            $session_save_path = implode ( ',', $session_save_path );
        }
        else
        {
            $session_save_path = 'tcp://'. $config [ 'server' ] .':'. $config [ 'port' ];
        }

        ini_set ( 'session.save_handler', 'memcache' );
        ini_set ( 'session.save_path', $session_save_path );
    }

    private function eaccelerator_init ( $config )
    {
        ini_set ( 'session.save_handler', 'eaccelerator' );
    }
    
    private function files_init ( $config )
    {
        $dir = XT_PROJECT_DIR .'/'. $config [ 'dir' ];
        if ( !file_exists ( $dir ) ) mkdir ( $dir, 0777, true );
        session_save_path ( $dir );
    }


    // Magic methods for manipulating session variables
    public function & __get ( $key )
    {
        if ( $this -> namespace != null )
        {
            return $_SESSION [ $this -> namespace ] [ $key ];
        }
        else
        {
            return $_SESSION [ $key ];
        }
    }
    public function __set ( $key, $value )
    {
        if ( $this -> namespace != null )
        {
            $_SESSION [ $this -> namespace ] [ $key ] = $value;
        }
        else
        {
            $_SESSION [ $key ] = $value;
        }
    }
    public function __isset ( $key )
    {
        return ( $this -> namespace != null ?
                 isset ( $_SESSION [ $this -> namespace ] [ $key ] ) :
                 isset ( $_SESSION [ $key ] ) );
    }
    public function __unset ( $key )
    {
        if ( $this -> namespace != null )
        {
            unset ( $_SESSION [ $this -> namespace ] [ $key ] );
        }
        else
        {
            unset ( $_SESSION [ $key ] );
        }
    }


    // Iterator methods
    function rewind ()
    {
        return ( $this -> namespace != null ?
                 reset ( $_SESSION [ $this -> namespace ] ) :
                 reset ( $_SESSION ) );
    }
    function current ()
    {
        return ( $this -> namespace != null ?
                 current ( $_SESSION [ $this -> namespace ] ) :
                 current ( $_SESSION ) );
    }
    function key ()
    {
        return ( $this -> namespace != null ?
                 key ( $_SESSION [ $this -> namespace ] ) :
                 key ( $_SESSION ) );
    }
    function next ()
    {
        return ( $this -> namespace != null ?
                 next ( $_SESSION [ $this -> namespace ] ) :
                 next ( $_SESSION ) );
    }
    function valid ()
    {
        return ( $this -> namespace != null ?
                 key ( $_SESSION [ $this -> namespace ] ) !== null:
                 key ( $_SESSION ) !== null );
    }
}