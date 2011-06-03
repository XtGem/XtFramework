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

class xt_mongo extends MongoDB
{

    private $_enable_logging = false;

    private $_connection = null,
            $_db_name = null,
            $_database = null,
            $_profiler = null,
            $_collections = array ();


    public function __construct ( $database = 'mongo' )
    {
        $config = X::get ( 'config', 'database', $database );

        $dsn = $config [ 'dsn' ];
        $this -> _db_name = $config [ 'database' ];
        $this -> _database = $database;

        // Configure custom profiler
        if ( isset ( $config [ 'profiler' ] ) && $config [ 'profiler' ] [ 'enabled' ] )
        {
            $this -> _enable_logging = true;
            $this -> _profiler = $config [ 'profiler' ];
            unset ( $config [ 'profiler' ] );
        }

        // Unset keys so that remaining array can be used as connection options
        unset ( $config [ 'dsn' ] );
        unset ( $config [ 'database' ] );

        try
        {
            $this -> _connection = new Mongo ( $dsn, $config );
        }
        catch ( MongoConnectionException $e )
        {
            throw new error ( $e -> getMessage () );
        }

        parent::__construct ( $this -> _connection, $this -> _db_name );
    }


    public function __get ( $col )
    {
        return $this -> selectCollection ( $col );
    }


    public function selectCollection ( $col )
    {
        if ( $this -> _enable_logging )
        {
            if ( isset ( $this -> _collections [ $col ] ) ) 
            {
                return $this -> _collections [ $col ];
            }
            
            $this -> _collections [ $col ] = new xt_mongo_collection (
                                            $this,
                                            $col,
                                            $this -> _database,
                                            $this -> _profiler );
            return $this -> _collections [ $col ];
        }
        else
        {
            return parent::selectCollection ( $col );
        }
    }

}