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

class m2
{

    private $db_config,
            $db_variables,
            $db_mdb,
            $collection = null,
            $object = null,
            $criteria = null,
            $order = null,
            $offset = null;


    /**
     * M2 constructor
     * @param $collection string
     * @param $database string
     * @public
     */
    public function __construct ( $collection, $database = null )
    {
        // If undefined, try setting database to controller name
        if ( $database == null )
        {
            $database = X::get ( 'controller' );
        }

        $this -> db_config = &X::get_reference ( 'config', 'database', $database );
        $this -> db_variables = &X::get_reference ( 'framework', 'databases', $database );
        $this -> db_mdb = &$variables [ 'mdb' ];

        // Can't proceed until DSN is set
        if ( !isset ( $this -> db_config [ 'dsn' ] ) )
        {
            throw new error ( 'Database connection failed' );
        }

        $mdb = new Mongo (
                            $this -> db_config [ 'dsn' ],
                            isset ( $this -> db_config [ 'options' ] ) ?
                                    $this -> db_config [ 'options' ] :
                                    array ( 'connect' => true, 'persistent' => true )
                        );
        $this -> db_mdb = $mdb -> selectDB ( $database );
        
        // Initialize M2
        $this -> collection = $collection;
        $this -> object = null;
    }


    /**
     * Destructor automatically commits all the remaining changes
     */
    public function __destruct ()
    {
        $this -> save ();
    }


	/**
		Retrieve from cache; or save query results to cache if not
		previously executed
			@param $query array
			@param $ttl integer
			@private
			@static
	**/
	private function cache(array $query, $ttl) {
		$hash = md5 ( serialize ( $query ) );
		$cached = X_cache::get ( $hash );
		if ( $cached )
		{
			// Retrieve from cache
			$this -> db_variables = $cached;
		}
		else
		{
			$this -> execute ( $query );
			// Save to cache
			X_cache::set ( $hash, $this -> db_variables );
		}
	}

    /**
     * Execute MongoDB query
     * @param $query array
     * @private
     * @static
     */
    private function execute ( $query )
    {
        // Execute
        $list = $this -> db_mdb -> listCollections ();

        foreach ( $list as &$collection )
            $collection = $collection -> getName ();

        if ( $query [ 'method' ] != 'save' &&
                !in_array ( $this -> collection, $list ) )
        {
            throw new error ( 'Collection `' . $this -> collection . '` does not exist' );
        }

        $out = call_user_func (
			array (
				$this -> db_mdb -> selectCollection ( $this -> collection ),
				$query [ 'method' ]
			),
			isset ( $query [ 'criteria' ] ) ?
				$query [ 'criteria' ] : array () ,
			isset ( $query [ 'fields' ] ) ?
				$query [ 'fields' ] : array ()
        );

		if ( isset ( $query [ 'mapreduce' ] ) ) {
			// Create temporary collection
			$ref = $this -> db_mdb -> selectCollection (
				'm2.'. md5 ( json_encode ( $query ) )
			);
			$ref -> batchInsert ( iterator_to_array ( $out, FALSE ) );
			$map = $query [ 'mapreduce' ];
			$func = 'function() {}';
			// Map-reduce
			$tmp = $this -> db_mdb -> command (
				array (
					'mapreduce' => $ref -> getName (),
					'map' => isset ( $map [ 'map' ] ) ?
						$map [ 'map' ] : $func,
					'reduce' => isset ( $map [ 'reduce' ] ) ?
						$map [ 'reduce' ] : $func,
					'finalize' => isset ( $map [ 'finalize' ] ) ?
						$map [ 'finalize' ] : $func
				)
			);
			if ( ! $tmp [ 'ok' ] )
			{
				throw new error ( $tmp [ 'errmsg' ] );
				return FALSE;
			}
			$ref -> remove ();
			foreach ( iterator_to_array (
				$this -> db_mdb -> selectCollection ( $tmp [ 'result' ] ) -> find (),
				FALSE ) as $aggregate )
			{
				$ref -> insert ( $aggregate ['_id'] );
			}
			$out = $ref -> find ();
		}
		
		if ( $query [ 'method' ] == 'find' )
		{
			if ( isset( $query [ 'order' ] ) )
				// Sort results
				$out = $out -> sort ( $query [ 'order' ] );
			if ( isset ( $query [ 'offset' ] ) )
				// Skip to record offset
				$out = $out -> skip ( $query [ 'offset' ] );
			if ( isset ( $query [ 'limit' ] ) )
				// Limit number of results
				$out = $out -> limit ( $query [ 'limit' ] );
			// Convert cursor to PHP array
			$this -> db_variables [ 'result' ] =
				iterator_to_array ( $out, FALSE );
		}
		else
			$this -> db_variables [ 'result' ] =
				array ( $query [ 'method' ] => $out );

		if ( isset ( $query [ 'mapreduce' ] ) )
			// Delete the temporary collection
			$ref -> drop ();
			
		// Clear the cursor
		unset ( $out );
		
		foreach ( $this -> db_variables [ 'result' ] as &$obj )
			// Convert MongoID to string
			if ( is_array ( $obj ) && isset ( $obj [ '_id' ] ) )
				$obj [ '_id' ] = $obj [ '_id' ] -> __toString ();
		return $this -> db_variables [ 'result' ];

    }


	/**
	 * Similar to M2 -> find method but provides more fine-grained control
	 * over specific fields and mapping-reduction of results
     * @return array
     * @param $fields array
     * @param $criteria mixed
     * @param $mapreduce mixed
     * @param $order mixed
     * @param $limit mixed
     * @param $offset mixed
     * @param $ttl integer
     * @public
	**/
	public function lookup (
		$fields,
		$criteria = null,
		$mapreduce = null,
		$order = null,
		$limit = null,
		$offset = null,
		$ttl = 0 ) {
		$query=array(
			'method' => 'find',
			'fields' => $fields,
			'criteria' => $criteria,
			'mapreduce' => $mapreduce,
			'order' => $order,
			'limit' => $limit,
			'offset' => $offset
		);
		if ( $ttl )
			$this -> cache ( $query, $ttl );
		else
			$this -> execute ( $query );
		return $this -> db_variables [ 'result' ];
	}

    /**
     * Return an array of collection objects matching criteria
     * @return array
     * @param $criteria mixed
     * @param $order mixed
     * @param $limit mixed
     * @param $offset mixed
     * @param $ttl integer
     * @public
     */
    public function find ( $criteria = null, $order = null, $limit = null, $offset = null, $ttl = 0 )
    {
        $query = array (
            'method' => 'find',
            'criteria' => $criteria,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset
        );
        if ( $ttl )
			$this -> cache ( $query, $ttl);
		else
			$this -> execute ( $query );
        return $this -> db_variables [ 'result' ];
    }


    /**
     * Return number of collection objects that match criteria
     * @return integer
     * @param $criteria mixed
     * @public
     */
    public function found ( $criteria = null )
    {
        $this -> execute (
                array (
                    'method' => 'count',
                    'criteria' => $criteria
                )
        );
        return $this -> db_variables [ 'result' ] [ 'count' ];
    }


    /**
     * Hydrate M2 with elements from array variable, keys of
     * which must be identical to field names in DB record
     * @param $array array
     * @public
     */
    public function copy_from ( $array )
    {
        if ( !empty ( $array ) )
        {
            foreach ( $array as $key => $value )
            {
                $this -> object [ $key ] = $value;
            }

            $this -> empty = false;
        }
    }


    /**
     * Dehydrate M2
     * @public
     */
    public function reset ()
    {
        // Dehydrate M2
        $this -> object = null;
        $this -> criteria = null;
        $this -> order = null;
        $this -> offset = null;
    }


    /**
     * Retrieve first collection object that satisfies criteria
     * @param $criteria mixed
     * @param $order mixed
     * @param $offset integer
     * @public
     */
    public function load ( $criteria = null, $order = null, $offset = 0 )
    {
        if ( !is_null ( $offset ) && $offset > -1 )
        {
            // Retrieve object
            $result = $this -> find ( $criteria, $order, 1, $offset );
            $this -> offset = null;
            if ( $result )
            {
                // Hydrate M2
                $this -> object = $result[ 0 ];
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
     * Retrieve N-th object relative to current using the same criteria
     * that hydrated M2
     * @param $count integer
     * @public
     */
    public function skip ( $count = 1 )
    {
        if ( $this -> dry () )
        {
            throw new error ( 'M2 is empty' );
        }
        self::load ( $this -> criteria, $this -> order, $this -> offset + $count );
    }


    /**
     * Insert/update collection object
     * @public
     */
    public function save ()
    {
        if ( is_null ( $this -> object ) )
        {
            return;
            //throw new error ( 'M2 is empty' );
        }
        // Let the MongoDB driver decide how to persist the
        // collection object in the database
        $obj = $this -> object;
        if ( is_array ( $obj ) && array_key_exists ( '_id', $obj ) )
            $obj [ '_id' ] = new MongoID ( $this -> object [ '_id' ] );
        $this -> execute ( array ( 'method' => 'save', 'criteria' => $obj ) );
    }


    /**
     * Delete collection object and reset M2
     * @public
     */
    public function erase ()
    {
        if ( is_null ( $this -> object ) )
        {
            throw new error ( 'M2 is empty' );
        }
        $this -> execute ( array ( 'method' => 'remove', 'criteria' => $this -> criteria ) );
        $this -> reset ();
    }


    /**
     * Return TRUE if M2 is null
     * @return boolean
     * @public
     */
    public function dry ()
    {
        return is_null ( $this -> object );
    }


    /**
     * Return value of M2-mapped field
     * @return boolean
     * @param $name string
     * @public
     */
    public function __get ( $name )
    {
        return $this -> object [ $name ];
    }


    /**
     * Assign value to M2-mapped field
     * @return boolean
     * @param $name string
     * @param $value mixed
     * @public
     */
    public function __set ( $name, $value )
    {
        $this -> object [ $name ] = $value;
    }


    /**
     * Clear value of M2-mapped field
     * @return boolean
     * @param $name string
     * @public
     */
    public function __unset ( $name )
    {
        unset ( $this -> object [ $name ] );
    }


    /**
     * Return TRUE if M2-mapped field exists
     * @return boolean
     * @param $name string
     * @public
     */
    public function __isset ( $name )
    {
        return array_key_exists ( $name, $this -> object );
    }

}