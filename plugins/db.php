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

class db
{
    /**
     * This class currently only supports MySQL.
     * Automatically enables UTF-8 support for query results. Comment it out if not required.
     */

    private $database_name = null,
            $db = null,
            $cache = array (),
            $res = null,
            $query_count = 0,
            $query_time = 0;
    


    public function __construct ( $database = null )
    {
        // If undefined, try setting database to controller name
        if ( $database == null )
        {
            $database = X::get ( 'controller' );
        }
        $this -> database_name = $database;
    }

    
    public function __destruct ()
    {
        // Disconnect
        if ( $this -> db != null )
            $this -> disconnect ();
    }

    
    public function connect ()
    {
        // Already connected
        if ( $this -> db != null )
            return false;

        if ( !X::is_set ( 'config', 'database', $this -> database_name ) )
        {
            throw new error ( 'Database `'. $this -> database_name .'` not found in configuration file' );
        }
        $config = X::get ( 'config', 'database', $this -> database_name );

        if ( !isset ( $config [ 'database' ] ) )
        {
            throw new error ( 'Database name not specified in config' );
        }
        
        $host = ( isset ( $config [ 'server' ] ) ? $config [ 'server' ] : 'localhost' );
        $user = ( isset ( $config [ 'username' ] ) ? $config [ 'username' ] : 'root' );
        $pass = ( isset ( $config [ 'password' ] ) ? $config [ 'password' ] :  null );

        error::silent ( true );
        $this -> db = mysql_connect ( $host, $user, $pass );
        error::silent ( false );
        
        if ( $this -> db === false )
        {
            throw new error ( 'Error occured while trying to connect to MySQL server.' );
        }

        $res = mysql_select_db ( $config [ 'database' ] );
        if ( $res === false )
        {
            throw new error ( 'Error occured while trying to connect to MySQL server.' );
        }

        $this -> query ( "SET NAMES 'utf8'" );

        return true;
    }


    public function disconnect ()
    {
        mysql_close ( $this -> db );
    }


    public function get_db ()
    {
        return $this -> db;
    }


    private function is_error ( $res = null, $qr = null )
    {
        $mysql_error = mysql_error ( $this -> db );

        if ( $mysql_error == null )
        {
            return $res;
        }
        else
        {
            throw new error ( 'MySQL Error (' . mysql_errno ( $this -> db ) . '): '. $mysql_error );
        }
    }


    public function query ( $qr, $cache = false )
    {
        $this -> connect ();

        if ( $cache && isset ( $this -> cache [ $qr ] ) )
        {
            return $this -> cache [ $qr ];
        }

        if ( X::get ( 'env' ) == 'dev' )
        {
            $qr_start = microtime ();
            $qr_start = array_sum ( explode ( ' ', $qr_start ) );
        }

        $res = mysql_query ( $qr, $this -> db );

        $this -> res = $this -> is_error ( $res, $qr );

        if ( X::get ( 'env' ) == 'dev' )
        {
            $qr_end = microtime ();
            $qr_end = array_sum ( explode ( ' ', $qr_end ) );
            $qr_diff = $qr_end - $qr_start;
            $this -> query_time += $qr_diff;
            $this -> query_count++;
        }

        if ( $cache )
        {
            $this -> cache [ $qr ] = $this -> res;
        }

        return $this -> res;
    }


    public function fetch ( $res = null )
    {
        if ( $res == null )
        {
            $res = $this -> res;
        }

        $fid = mysql_fetch_array ( $res );

        return $fid;
    }


    public function fetch_assoc ( $res = null )
    {
        if ( $res == null )
        {
            $res = $this -> res;
        }

        $fid = mysql_fetch_assoc ( $res );

        return $fid;
    }


    public function num_rows ( $res = null )
    {
        if ( $res == null )
        {
            $res = $this -> res;
        }

        $nr = mysql_num_rows ( $res );

        return $nr;
    }


    public function insert_id ()
    {
        return mysql_insert_id ( $this -> db );
    }


    public function affected_rows ()
    {
        return mysql_affected_rows ( $this -> db );
    }


    public function get_one ( $qr, $cache = false )
    {
        if ( !preg_match ( "#LIMIT 1\$#i", $qr ) )
        {
            $qr .= ' LIMIT 1';
        }

        $query = $this -> query ( $qr, $cache );
        $fetch = $this -> fetch ( $query );

        return ( isset ( $fetch [ 0 ] ) ? $fetch [ 0 ] : null );
    }


    public function get_row ( $qr, $cache = false )
    {
        if ( !preg_match ( "#LIMIT 1\$#i", $qr ) )
        {
            $qr .= ' LIMIT 1';
        }

        $query = $this -> query ( $qr, $cache );
        $fetch = $this -> fetch_assoc ( $query );

        return $fetch;
    }


    public function get_all ( $qr, $cache = false )
    {
        $query = $this -> query ( $qr, $cache );

        $ret = array ( );
        while ( $fetch = $this -> fetch_assoc ( $query ) )
        {
            $ret[ ] = $fetch;
        }

        return $ret;
    }


    public function insert ( $qr )
    {
        $this -> query ( $qr );
        return $this -> insert_id ();
    }


    public function insert_array ( $table, $data )
    {
        $query = 'INSERT INTO ' . $table . ' SET ';

        $count = count ( $data );

        foreach ( $data as $k => $v )
        {
            $query .= '`' . $k . '`=\'' . $v . '\', ';
        }

        $query = substr ( $query, 0, strlen ( $query ) - 2 ) . ' ';

        $this -> query ( $query );
        return $this -> insert_id ();
    }


    public function update ( $qr )
    {
        $this -> query ( $qr );
        return $this -> affected_rows ();
    }


    public function update_array ( $table, $data, $where )
    {
        $query = 'UPDATE ' . $table . ' SET ';

        foreach ( $data as $k => $v )
        {
            $query .= '`' . $k . '`=\'' . $this -> escape ( $v ) . '\', ';
        }

        $query = substr ( $query, 0, strlen ( $query ) - 2 ) . ' ';

        $query .= 'WHERE ' . $where;

        $this -> query ( $query );
        return $this -> affected_rows ();
    }


    public function insert_update_array ( $table, $data )
    {
        $query = 'INSERT INTO ' . $table . ' SET ';

        $count = count ( $data );

        foreach ( $data as $k => $v )
        {
            $data = '`' . $k . '`=\'' . $v . '\', ';
        }

        $data = substr ( $data, 0, strlen ( $data ) - 2 ) . ' ';

        $query .= $data . ' ON DUPLICATE KEY UPDATE ' . $data;

        $this -> query ( $query );
        return $this -> insert_id ();
    }


    public function delete ( $qr )
    {
        $this -> query ( $qr );
        return $this -> affected_rows ();
    }


    public function escape ( $txt, $trim = true, $quotesonly = false )
    {
        if ( $trim )
        {
            $txt = trim ( $txt );
        }

        if ( $quotesonly )
        {
            return strtr ( $txt, array ( "'" => "\\'", '"' => '\\"' ) );
        }

        $this -> connect ();

        return mysql_real_escape_string ( $txt, $this -> db );
    }

    
    // Returns number of executed queries and their total duration
    public function get_debug_info ()
    {
        return array ( $this -> query_count, $this -> query_time );
    }

}