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

class xt_mongo_collection extends MongoCollection
{

    private $_name = null;
    private $_db_name = null;
    private $_profiler_config = array ();


    public function __construct ( $db, $name, $database, $profiler_config )
    {
        $this -> _name = $name;
        $this -> _db_name = $database;
        $this -> _profiler_config = $profiler_config;
        parent::__construct ( $db, $name );
    }


    private function _profiler ()
    {
        if ( PHP_SAPI != 'cli' )
        {
            $url = $_SERVER [ 'REQUEST_METHOD' ] .' '.
                   $_SERVER [ 'HTTP_HOST' ] .
                   $_SERVER [ 'REQUEST_URI' ];
        }
        else
        {
            $url = 'cli';
        }

        $trace = debug_backtrace ();
        $data = array_merge ( array ( $url, $this -> _db_name, $this -> _name, $trace [ 1 ] ), func_get_args ());

        $key = $this -> _profiler_config [ 'cache_log_key' ] . $this -> _db_name;

        $log = X_cache::get ( $key );
        if ( !$log )
        {
            $log = serialize ( array ( $data ) );
        }
        else
        {
            $log = unserialize ( $log );
            array_unshift ( $log, $data );
            $log = array_slice ( $log, 0, $this -> _profiler_config [ 'log_length' ] );
            $log = serialize ( $log );
        }

        X_cache::set ( $key, $log, $this -> _profiler_config [ 'log_timeout' ] );

        $this -> _profiler = array ();
    }
    

    // Overloaded MongoCollection methods

    public function count ( $query = array (), $limit = 0, $skip = 0 )
    {
        $s = microtime ( true );

        $ret = parent::count ( $query, $limit, $skip );

        $diff = microtime ( true ) - $s;
        if ( $diff > $this -> _profiler_config [ 'slow_log' ] )
        {
            $this -> _profiler ( 'count', array ( $query, $limit, $skip ), $diff,
                                 parent::count ( $query, $limit, $skip ) -> explain () );
        }

        return $ret;
    }

    public function ensureIndex ( $keys, $options = array () )
    {
        $s = microtime ( true );
        $ret = parent::ensureIndex ( $keys, $options );
        $diff = microtime ( true ) - $s;
        if ( $diff > $this -> _profiler_config [ 'slow_log' ] )
        {
            $this -> _profiler ( 'ensureIndex', array ( $keys, $options ), $diff );
        }

        return $ret;
    }

    public function find ( $query = array (), $fields = array () )
    {
        $s = microtime ( true );

        $ret = parent::find ( $query, $fields );

        $diff = microtime ( true ) - $s;
        if ( $diff > $this -> _profiler_config [ 'slow_log' ] )
        {
            $this -> _profiler ( 'find', array ( $query, $fields ), $diff,
                                 parent::find ( $query, $fields ) -> explain () );
        }

        return $ret;
    }

    public function findOne ( $query = array (), $fields = array () )
    {
        $s = microtime ( true );

        $ret = parent::findOne ( $query, $fields );

        $diff = microtime ( true ) - $s;
        if ( $diff > $this -> _profiler_config [ 'slow_log' ] )
        {
            $this -> _profiler ( 'findOne', array ( $query, $fields ), $diff );
        }

        return $ret;
    }

    public function save ( $a, $options = array () )
    {
        $s = microtime ( true );

        $ret = parent::save ( $a, $options );

        $diff = microtime ( true ) - $s;
        if ( $diff > $this -> _profiler_config [ 'slow_log' ] )
        {
            $this -> _profiler ( 'save', array ( $a, $options ), $diff );
        }

        return $ret;
    }
    
}
