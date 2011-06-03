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

class xt_pdo
{

    /**
     * @todo benchmark + notifications
     */

    protected
        $db = null,
        $db_config,
        $db_variables;
    public
        $db_pdo;


    public function __construct ( $database )
    {
        $this -> boot ( $database );
    }


    /**
     * PDO bootstrap
     * @param string $database
     */
    public function boot ( $database )
    {
        // If undefined, try setting database to controller name
        $this -> db = is_null ( $database ) ?
            X::get ( 'controller' ) -> __toString () : $database;

        $this -> db_config =
            &X::get_reference ( 'config', 'database', $this -> db );

        $this -> db_variables =
            &X::get_reference ( 'framework', 'databases', $this -> db );
    }

    
    /**
     * Execute SQL statement
     * @param $query string
     * @param $database string
     * @public
     */
    public function execute ( $query )
    {
        if ( !isset ( $this -> db_pdo ) )
        {
            $this -> init_db_pdo ();
        }
        if ( X::get ( 'env' ) == 'dev' )
        {
            $qr_start = microtime ();
            $qr_start = array_sum ( explode ( ' ', $qr_start ) );
        }

        // Execute SQL statement
        $this -> db_variables [ 'query' ] = $this -> db_pdo -> query ( $query, PDO::FETCH_LAZY );

        // Check SQLSTATE
        if ( $this -> db_pdo -> errorCode () != '00000' )
        {
            // Gather info about error
            $error = $this -> db_pdo -> errorInfo ();
            $env = X::get ( 'env' );
            throw new error ( 'Database error - ' .
                $error [ 0 ] . ' (' . $error [ 1 ] . ') ' . $error [ 2 ] .
                ( $env == 'dev' || $env == 'cli' ? "\n". $query : null ) );
            $this -> db_variables [ 'error' ] = $error;
        }

        // Save result
        $this -> db_variables [ 'result' ] =
            $this -> db_variables [ 'query' ] ->
                fetchAll ( PDO::FETCH_ASSOC );

        if ( X::get ( 'env' ) == 'dev' )
        {
            $qr_end = microtime ();
            $qr_end = array_sum ( explode ( ' ', $qr_end ) );
            $qr_diff = $qr_end - $qr_start;
            $variables = &X::get_reference ( 'framework', 'databases' );
            if ( !isset ( $variables [ 'query_time' ] ) )
                $variables [ 'query_time' ] = 0;
            if ( !isset ( $variables [ 'query_counter' ] ) )
                $variables [ 'query_counter' ] = 0;
            $variables [ 'query_time' ] += $qr_diff;
            $variables [ 'query_counter' ]++;
        }
    }


    /**
     * Retrieve from cache; or save SQL query results to cache if not
     * previously executed
     * @param $query string
     * @param $database string
     * @param $ttl integer
     * @private
     * @static
     */
    private function cache ( $query, $ttl )
    {
        $hash = 'axon_' . md5 ( $query );
        $cached = X_cache::get ( $hash );
        if ( $cached )
        {
            // Retrieve from cache
            $this -> db_variables = $cached;
        }
        else
        {
            $this -> execute ( $query );
            unset ( $this -> db_variables [ 'query' ] );
            // Save to cache
            X_cache::set ( $hash, $this -> db_variables, $ttl );
        }
    }

    private function init_db_pdo ()
    { 
        $ext = substr ( $this -> db_config [ 'dsn' ], 0,
                            strpos ( $this -> db_config [ 'dsn' ], ':' ) );
        if ( !extension_loaded ( $ext ) )
        {
            // PHP extension not activated
            throw new error ( 'PHP extension `' . $ext . '` is not enabled' );
        }

        /*if ( ! isset ($this -> db_config [ 'options' ] ) ||
                is_null ( $this -> db_config [ 'options' ] ) )
                $this -> db_config [ 'options' ] = array (
                        // Retain data types
                        PDO::ATTR_EMULATE_PREPARES => false
                );*/
        if ( isset ( $this -> db_config [ 'options' ] ) && $this -> db_config [ 'options' ] != null )
        {
            foreach ( $this -> db_config [ 'options' ] as $k => $v )
            {
                    if ( is_string ( $k ) )
                    {
                        unset ( $this -> db_config [ 'options' ] [ $k ] );
                        $this -> db_config [ 'options' ] [ constant ( $k ) ] = $v;
                    }
            }
        }

        $this -> db_pdo = new PDO (
                        $this -> db_config [ 'dsn' ],
                        ( isset ( $this -> db_config [ 'username' ] ) ?
                                $this -> db_config [ 'username' ] : null ),
                        ( isset ( $this -> db_config [ 'password' ] ) ?
                                $this -> db_config [ 'password' ] : null ),
                        ( isset ( $this -> db_config [ 'options' ] ) ?
                                $this -> db_config [ 'options' ] : null )
        );

        if ( !$this -> db_pdo )
        {
            // Unable to connect
            throw new error ( 'Database connection failed' );
        }

        // Define connection attributes
        $attrs = array ( 'AUTOCOMMIT', 'ERRMODE', 'CASE', 'CLIENT_VERSION',
            'CONNECTION_STATUS', 'PERSISTENT', 'PREFETCH',
            'SERVER_INFO', 'SERVER_VERSION', 'TIMEOUT' );

        // Save attributes in DB global variable
        foreach ( $attrs as $attr )
        {
            // Suppress warning if PDO driver doesn't support attribute
            error::silent ( true );
            $val = $this -> db_pdo ->
                            getAttribute ( constant ( 'PDO::ATTR_' . $attr ) );
            error::silent ( false );

            if ( isset ( $val ) && $val )
            {
                $this -> db_variables [ 'attributes' ][ $attr ] = $val;
            }
        }
    }


    /**
     * Process SQL statement(s), create PDO object if it doesn't exist
     * @return mixed
     * @param $query mixed
     * @param $database string
     * @param $ttl integer
     * @public
     */
    public function sql ( $query, $ttl = 0 )
    {
        if ( is_null ( $this -> db_pdo ) )
        {
            $this -> init_db_pdo ();

            $ext = 'pdo_'. substr ( $this -> db_config [ 'dsn' ], 0,
                strpos ( $this -> db_config [ 'dsn' ], ':' ) );
            if ( !extension_loaded ( $ext ) )
            {
                // PHP extension not activated
                throw new error ( 'PHP extension `' . $ext . '` is not enabled' );
            }

            /*
            if ( ! isset ($this -> db_config [ 'options' ] ) ||
                is_null ( $this -> db_config [ 'options' ] ) )
                $this -> db_config [ 'options' ] = array (
                    // Retain data types
                    PDO::ATTR_EMULATE_PREPARES => false
            );*/
            if ( isset ( $this -> db_config [ 'options' ] ) && $this -> db_config [ 'options' ] != null )
            {
                foreach ( $this -> db_config [ 'options' ] as $k => $v )
                {
                    if ( is_string ( $k ) )
                    {
                        unset ( $this -> db_config [ 'options' ] [ $k ] );
                        $this -> db_config [ 'options' ] [ constant ( $k ) ] = $v;
                    }
                }
            }

            $this -> db_pdo = new PDO (
                $this -> db_config [ 'dsn' ],
                ( isset ( $this -> db_config [ 'username' ] ) ?
                        $this -> db_config [ 'username' ] : null ),
                ( isset ( $this -> db_config [ 'password' ] ) ?
                        $this -> db_config [ 'password' ] : null ),
                ( isset ( $this -> db_config [ 'options' ] ) ?
                        $this -> db_config [ 'options' ] : null )
            );

            if ( !$this -> db_pdo )
            {
                // Unable to connect
                throw new error ( 'Database connection failed' );
            }

            // Define connection attributes
            /*
            $attrs = array ( 'AUTOCOMMIT', 'ERRMODE', 'CASE', 'CLIENT_VERSION',
                'CONNECTION_STATUS', 'PERSISTENT', 'PREFETCH',
                'SERVER_INFO', 'SERVER_VERSION', 'TIMEOUT' );

            // Save attributes in DB global variable
            foreach ( $attrs as $attr )
            {
                // Suppress warning if PDO driver doesn't support attribute
                //error::silent ( true );
                $val = $this -> db_pdo ->
                    getAttribute ( constant ( 'PDO::ATTR_' . $attr ) );
                //error::silent ( false );

                if ( isset ( $val ) && $val )
                {
                    $this -> db_variables [ 'attributes' ][ $attr ] = $val;
                }
            }
            */
        }

        // Can't proceed until DSN is set
        if ( !isset ( $this -> db_config [ 'dsn' ] ) )
        {
            throw new error ( 'Database connection failed' );
        }

        if ( preg_match ( '#^(sqlite[2]*\:)(.+)$#', $this -> db_config [ 'dsn' ], $db_path ) )
        {
            // Modify DSN if relative DB path specified
            if ( $db_path [ 2 ] [ 0 ] == '.' )
            {
                // Assume it begins with ./
                $this -> db_config [ 'dsn' ] = $db_path [ 1 ] . XT_PROJECT_DIR . substr ( $db_path [ 2 ], 1 );
            }
            $this -> db_config [ 'dsn' ] = str_replace ( '/',
                 DIRECTORY_SEPARATOR,
                 $this -> db_config [ 'dsn' ] );
        }

        // Convert to array to prevent code duplication
        if ( !is_array ( $query ) )
        {
            $query = array ( $query );
        }

        $this -> db_variables [ 'result' ] = null;

        // More than one SQL statement specified
        if ( count ( $query ) > 1 )
        {
            $this -> db_pdo -> beginTransaction ();
            $multiple_queries = true;
        }

        foreach ( $query as $q )
        {
            if ( $ttl )
            {
                // Cache results
                $this -> cache ( $q, $ttl );
            }
            else
            {
                $this -> execute ( $q );
            }
        }

        if ( isset ( $multiple_queries ) )
        {
            $func = ( isset ( $this -> db_variables [ 'error' ] ) ?
                'rollBack' : 'commit' );
            call_user_func ( array ( $this -> db_pdo, $func ) );
        }
        return $this -> db_variables [ 'result' ];
    }


    /**
     * Convert characters that need to be quoted in database queries to
     * XML entities; quote string and add commas between each argument
     * @return string
     * @public
     */
    public function qq ()
    {
        $args = func_get_args();

        $text = null;
        foreach ( $args as $arg )
        {
            $text .= ( $text ? ',' : null ) .
                ( is_string ( $arg ) ?
                    ('"' . str_replace ( '"', '&#34;', $arg ) . '"') :
                    $arg );
        }

        return $text;
    }


    /**
     * Execute a SQL text dump against the database
     * @param string $sql
     */
    public function execute_dump ( $sql )
    {
        set_time_limit ( 0 );

        //$queries = array ();

        // Strip out comments
        $sql = preg_replace ( "#^\s*\-\-.*$#m", null, $sql );

        // Replace carriage returns and newlines into spaces
        $sql = strtr ( $sql, array ( "\r" => ' ', "\n" => ' ' ) );

        $in_string = false;
        $from = 0;
        for ( $pos = 0, $max = strlen ( $sql ); $pos < $max; $pos++ )
        {
            switch ( $sql [ $pos ] )
            {
                case '"':
                case "'":
                    if ( !$in_string )
                    {
                        $in_string = $sql [ $pos ];
                    }
                    elseif ( $in_string && $in_string == $sql [ $pos ] )
                    {
                        $in_string = false;
                    }
                    break;

                case ';':
                    if ( !$in_string )
                    {
                        $query = trim ( substr ( $sql, $from, ($pos-$from) ) );
                        $from = $pos +2;
                        if ( $query == null ) continue;
                        $this -> sql ( $query );
                    }
                    break;
            }
        }
        $query = trim ( substr ( $sql, $from, ($pos-$from) ) );
        if ( $query != null )
        {
            $this -> sql ( $query );
        }

        return true;
    }

    public function insert_id (  )
    {
        $id = $this -> sql ( 'SELECT LAST_INSERT_ID() AS lid' );

        if ( $id && is_array ( $id ) && isset ( $id [ 0 ] [ 'lid' ] ) )
        { 
            return $id [ 0 ] [ 'lid' ] ;
        }
        else
        { 
            return false;
        }
    }

    public function __sleep ()
    {
        throw new error ( 'PDO serialization attempt detected!' );
    }
}
