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

    private $_db_name = null,
            $_database = null,
            $_profiler = null,
            $_collections = array ();

    private static $_connections = array ();

    public function __construct ( $database = false, $force_new_object = false, $force_slave_mode = null )
    {
        if ( !$database )
        {
            $database = X::is_set ( 'X_MONGO_MAIN_DATABASE' ) ? X::get ( 'X_MONGO_MAIN_DATABASE' ) : 'mongo';
        }
        
        $config = X::get ( 'config', 'database', $database );

        $dsn = $config [ 'dsn' ];
        $this -> _db_name = $config [ 'database' ];
        $this -> _database = $database;

        // Configure custom profiler
        if ( isset ( $config [ 'profiler' ] ) )
        {
            if ( $config [ 'profiler' ] [ 'enabled' ] )
            {
                $this -> _enable_logging = true;
                $this -> _profiler = $config [ 'profiler' ];
            }
                
            unset ( $config [ 'profiler' ] );
        }

        $slave_ok = ( isset ( $config [ 'slaveOkay' ] ) && $config [ 'slaveOkay' ] == 'true' );
        $slave_ok_force = ( !isset ( $config [ 'slaveOkay_force' ] ) || $config [ 'slaveOkay_force' ] == 'true' );
        
        // Unset keys so that remaining array can be used as connection options
        unset ( $config [ 'dsn' ] );
        unset ( $config [ 'database' ] );
        unset ( $config [ 'slaveOkay' ] );
        unset ( $config [ 'slaveOkay_force' ] );

        if ( !isset ( self::$_connections [ $database ] ) || !is_object ( self::$_connections [ $database ] ) || $force_new_object )
        {
            try
            {
                if ( class_exists ( 'MongoClient' ) )
                {
                    // Persist option not supported in 1.3.smth+?
                    unset ( $config [ 'persist' ] );
                    self::$_connections [ $database ] = new MongoClient ( $dsn, $config );
                }
                else
                {
                    self::$_connections [ $database ] = new Mongo ( $dsn, $config );
                }
            }
            catch ( MongoConnectionException $e )
            {
                throw new error ( $e -> getMessage () );
            }

            // Set slaveOkay state
            if ( method_exists ( self::$_connections [ $database ], 'setSlaveOkay' ) )
            {
                if ( $force_slave_mode !== null )
                {
                    self::$_connections [ $database ] -> setSlaveOkay ( ( bool ) $force_slave_mode );
                }
                else
                {
                    self::$_connections [ $database ] -> setSlaveOkay ( $slave_ok );
                }
            }
            else if ( $slave_ok && $slave_ok_force )
            {
                throw new error ( 'Mongo configured with slaveOkay: true, but current PHP mongo library does not support per-connection slaveOkay. Update to >=1.1.0 or set slaveOkay_force: false' );
            }
        }
        
        parent::__construct ( self::$_connections [ $database ], $this -> _db_name );
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
