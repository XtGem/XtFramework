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

class axon extends xt_pdo
{

    private
            $initialized = false,
            $table = null,
            $keys = array (),
            $criteria = null,
            $order = null,
            $offset = null,
            $fields = array (),
            $virtual = array (),
            $_empty = true;


    /**
     * Axon constructor
     * @public
     */
    public function __construct ( $table = null, $database = null )
    {
        // Initialize Axon
        $this -> db = $database;
        $this -> table = $table;
        $this -> boot ( $database );
    }

    
    /**
     * Initialize axon
     */
    protected function init ()
    {
         // Already initialized
        if ( $this -> initialized ) return;

        // MySQL schema
        if ( preg_match ( '#^mysql\:#', $this -> db_config [ 'dsn' ] ) )
        {
            $query = 'SHOW columns FROM '. $this -> table .';';
            $fields = array ( 'Field', 'Key', 'PRI' );
        }

        // SQLite schema
        elseif ( preg_match ( '#^sqlite[2]*\:#', $this -> db_config [ 'dsn' ] ) )
        {
            $query = 'PRAGMA table_info('. $this -> table .');';
            $fields = array ( 'name', 'pk', 1 );
        }

        // SQL Server/Sybase/DBLib/ProgreSQL schema
        elseif ( preg_match ( '#^(mssql|sybase|dblib|pgsql)\:#', $this -> db_config [ 'dsn' ] ) )
        {
            $query = 'SELECT C.column_name AS field,T.constraint_type AS key ' .
                    'FROM information_schema.columns C ' .
                    'LEFT OUTER JOIN information_schema.key_column_usage K ' .
                    'ON C.table_name=K.table_name AND ' .
                    'C.column_name=K.column_name ' .
                    'LEFT OUTER JOIN information_schema.table_constraints T ' .
                    'ON K.table_name=T.table_name AND ' .
                    'K.constraint_name=T.constraint_name ' .
                    'WHERE C.table_name="'. $thos -> table .'";';
            $fields = array ( 'field', 'key', 'PRIMARY KEY' );
        }

        // Unsupported DB engine
        else
        {
            throw new error ( 'Database engine is not supported' );
        }

        $this -> initialized = true;

        $sync = ( X::is_set ( 'config', 'database', $this -> db, 'sync' ) ? 
                    X::get ( 'config', 'database', $this -> db, 'sync' ) :
                    0 );

        $result = $this -> sql ( $query, $sync );

        if ( !$result )
        {
            throw new error ( 'Unable to map table `'. $this -> table .'`' );
        }

        foreach ( $result as $col )
        {
            // Populate properties
            $this -> fields [ $col [ $fields[ 0 ] ] ] = null;

            // Save primary key
            if ( $col [ $fields[ 1 ] ] == $fields [ 2 ] )
                $this -> keys [ $col [ $fields[ 0 ] ] ] = null;
        }
        
        $this -> _empty = true;
    }

    
    /**
     * Similar to Axon->find method but provides more fine-grained control
     * over specific fields and grouping of results
     * @param $fields string
     * @param $criteria mixed
     * @param $grouping mixed
     * @param $order mixed
     * @param $limit mixed
     * @public
     */
    public function lookup ( $fields, $criteria = null, $grouping = null,
                                      $order = null, $limit = null )
    {
        $this -> init ();
        return $this -> sql (
			'SELECT ' . $fields . ' FROM ' . $this -> table.
				(is_null ( $criteria ) ? '' : (' WHERE ' . $criteria)) .
				(is_null ( $grouping ) ? '' : (' GROUP BY ' . $grouping)) .
				(is_null ( $order ) ? '' : (' ORDER BY ' . $order)) .
				(is_null ( $limit ) ? '' : (' LIMIT ' . $limit)) . ';'
        );
    }


    /**
     * Alias of the lookup method
     * @public
     */
    public function select ()
    {
        // PHP doesn't allow direct use as function argument
        $args = func_get_args();
        return call_user_func_array ( array ( $this, 'lookup' ), $args );
    }


    /**
     * Return an array of DB records matching criteria
     * @return array
     * @param $criteria mixed
     * @param $order mixed
     * @param $limit mixed
     * @public
     */
    public function find ( $criteria = null, $order = null, $limit = null )
    {
        return $this -> lookup ( '*', $criteria, null, $order, $limit );
    }


    /**
     * Return number of DB records that match criteria
     * @return integer
     * @param $criteria mixed
     * @public
     */
    public function found ( $criteria = null )
    {
        $result = $this -> lookup ( 'COUNT(*) AS found', $criteria );
        return $result [ 0 ][ 'found' ];
    }


    /**
     * Hydrate Axon with elements from array variable, keys of
     * which must be identical to field names in DB record
     * @param $array array
     * @public
     */
    public function copy_from ( $array )
    {
        $this -> init ();

        if ( !empty ( $array ) )
        {
            foreach ( $array as $key => $value )
            {
                if ( array_key_exists ( $key, $this -> fields ) )
                {
                    $this -> fields [ $key ] = $value;
                }
            }
            
            $this -> _empty = false;
        }
    }


    /**
     * Dehydrate Axon
     * @public
     */
    public function reset ()
    {
        // Null out fields
        foreach ( $this->fields as &$field )
			$field = null;

        // Null out primary keys
        foreach ( $this->keys as &$key )
			$key = null;

        // Dehydrate Axon
        $this -> _empty = true;
        $this -> criteria = null;
        $this -> order = null;
        $this -> offset = null;
    }


    /**
     * Retrieve first DB record that satisfies criteria
     * @param $criteria mixed
     * @param $order mixed
     * @param $offset integer
     * @public
     */
    public function load ( $criteria = null, $order = null, $offset = 0 )
    {
        $this -> init ();
        
        if ( !is_null ( $offset ) && $offset > -1 )
        {
            $virtual = null;

            foreach ( $this -> virtual as $field => $value )
            {
                $virtual .= ',('. $value [ 'expr' ] .') AS '. $field;
            }

            // Retrieve record
            $result = $this -> lookup (
                '*' . $virtual, $criteria, null, $order, '1 OFFSET ' . $offset
            );

            $this -> offset = null;
            
            if ( $result )
            {
                // Hydrate Axon
                foreach ( $result [ 0 ] as $field => $value )
                {
                    if ( array_key_exists ( $field, $this -> fields ) )
                    {
                        $this -> fields [ $field ] = $value;
                        if ( array_key_exists ( $field, $this -> keys ) )
                            $this -> keys [ $field ] = $value;
                    }
                    else
                        $this -> virtual [ $field ] [ 'value' ] = $value;
                }
                $this -> _empty = false;
                $this -> criteria = $criteria;
                $this -> order = $order;
                $this -> offset = $offset;
            }
            else
                $this -> reset ();
        }
        else
            $this -> reset ();
    }


    /**
     * Retrieve N-th record relative to current using the same criteria
     * that hydrated the Axon
     * @param $count integer
     * @public
     */
    public function skip ( $count = 1 )
    {
        $this -> init ();

        if ( $this -> dry () )
        {
            throw new error ( 'Axon is empty' );
        }
        $this -> load ( $this -> criteria, $this -> order, $this -> offset + $count );
    }


    /**
     * Insert/update DB record
     * @public
     */
    public function save ()
    {
        if ( !$this -> initialized ) return;

        if ( $this -> _empty )
        {
            return;
            //throw new error ( 'Axon is empty' );
        }

        $new = true;
        if ( $this -> keys )
        {
            // If ALL primary keys are NULL, this is a new record
            foreach ( $this -> keys as $value )
            {
                if ( !is_null ( $value ) )
                {
                    $new = false;
                    break;
                }
            }
        }

        // Insert new record
        if ( $new )
        {
            $fields = null;
            $values = null;
            
            foreach ( $this -> fields as $field => $value )
            {
                $fields .= ( $fields ? ',' : null ) . $field;
                $values .= ( $values ? ',' : null ) .
                        ( is_null ( $value ) ? 'NULL' :
						( is_numeric ( $value )? $value:
								$this -> qq ( $value ) ) );
            }
            
            $this -> sql (
                    'INSERT INTO ' . $this -> table . ' (' . $fields . ') ' .
                    'VALUES (' . $values . ');'
            );

            $id = $this -> sql ( 'SELECT LAST_INSERT_ID() as id' );

            if ( isset ( $id [ 0 ][ 'id' ] ) )
            {
                return $id [ 0 ][ 'id' ];
            }
        }

        // Update record
        else
        {
            $set = null;
            foreach ( $this -> fields as $field => $value )
                $set.= ( $set ? ',' : null ) . $field .'='.
                            ( is_null ( $value ) ? 'NULL' :
							( is_numeric ( $value )? $value:
								$this -> qq ( $value ) ) );

            // Use prior primary key values (if changed) to find record
            $cond = null;
            
            foreach ( $this -> keys as $key => $value )
            {
                $cond .= ( $cond ? ' AND ' : null ) . $key .
                            ( is_null ( $value ) ? ' IS NULL' :
                              ( '=' . ( is_numeric ( $value )? $value:
								$this -> qq ( $value ) ) ) );
            }

            $this -> sql (
                    'UPDATE '. $this -> table .' SET '. $set .
                        ( is_null ( $cond ) ? null : ' WHERE '. $cond ) .';'
            );
        }

        if ( $this -> keys )
        {
            // Update primary keys with new values
            reset ( $this -> keys );
            while ( list($field, ) = each ( $this -> keys ) )
                $this -> keys [ $field ] = $this -> fields [ $field ];
        }
    }


    /**
     * Delete DB record and reset Axon
     * @public
     */
    public function erase ()
    {
        $this -> init ();

        if ( $this -> _empty )
        {
            throw new error ( 'Axon is empty' );
        }

        $cond = $this -> criteria;
        
        $this -> sql (
                'DELETE FROM ' . $this -> table .
                    ( is_null ( $cond ) ? null : ' WHERE '. $cond ) .';'
        );
        
        $this -> reset ();
    }


    /**
     * Return TRUE if Axon is devoid of values in its properties
     * @return boolean
     * @public
     */
    public function dry ()
    {
        $this -> init ();

        return $this -> _empty;
    }


    /**
     * Create a virtual field
     * @param $name string
     * @param $expr string
     * @public
     */
    public function def ( $name, $expr )
    {
        $this -> init ();

        if ( array_key_exists ( $name, $this -> fields ) )
        {
            throw new error ( 'Name conflict with Axon-mapped field' );
        }
        if ( !is_string ( $expr ) || !strlen ( $expr ) )
        {
            throw new error ( 'Invalid virtual field expression' );
        }
        $this -> virtual [ $name ] [ 'expr' ] = $expr;
    }


    /**
     * Destroy a virtual field
     * @param $name string
     * @public
     */
    public function undef ( $name )
    {
        $this -> init ();

        if ( array_key_exists ( $name, $this -> fields ) )
        {
            throw new error ( 'Cannot undefine an Axon-mapped field' );
        }
        if ( !array_key_exists ( $name, $this -> virtual ) )
        {
            throw new error ( 'The field ' . $name . ' does not exist' );
        }
        unset ( $this -> virtual [ $name ] );
    }


    /**
     * Return TRUE if virtual field exists
     * @param $name
     * @public
     */
    public function isdef ( $name )
    {
        $this -> init ();

        return array_key_exists ( $name, $this -> virtual );
    }

    
    /**
     * Return value of Axon-mapped/virtual field
     * @return boolean
     * @param $name string
     * @public
     */
    public function __get ( $name )
    {
        $this -> init ();

        if ( array_key_exists ( $name, $this -> fields ) )
            return $this -> fields [ $name ];
        if ( array_key_exists ( $name, $this -> virtual ) )
            return $this -> virtual [ $name ] [ 'value' ];
        
        throw new error ( 'The field `' . $name . '` does not exist' );
    }


    /**
     * Assign value to Axon-mapped field
     * @return boolean
     * @param $name string
     * @param $value mixed
     * @public
     */
    public function __set ( $name, $value )
    {
        $this -> init ();

        if ( array_key_exists ( $name, $this -> fields ) )
        {
            $this -> fields [ $name ] = $value;

            // Axon is now hydrated
            if ( !is_null ( $value ) ) $this -> _empty = false;
            
            return;
        }
        if ( array_key_exists ( $name, $this -> virtual ) )
        {
            throw new error ( 'Virtual fields are read-only' );
        }
        
        throw new error ( 'The field `' . $name . '` does not exist' );
    }


    /**
     * Clear value of Axon-mapped field
     * @return boolean
     * @param $name string
     * @public
     */
    public function __unset ( $name )
    {
        $this -> init ();

        if ( array_key_exists ( $name, $this -> fields ) )
        {
            throw new error ( 'Cannot unset an Axon-mapped field' );
        }
        
        throw new error ( 'The field ' . $name . ' does not exist' );
    }


    /**
     * Return TRUE if Axon-mapped/virtual field exists
     * @return boolean
     * @param $name string
     * @public
     */
    public function __isset ( $name )
    {
        $this -> init ();
        
        return array_key_exists (
                $name, array_merge ( $this -> fields, $this -> virtual )
        );
    }

    /**
     * Return all the fields from private variable
     */
    public function get_fields ()
    {
        return $this -> fields;
    }


}
