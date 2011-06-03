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

class xt_doctrine_connection
{

    private 
            $manager = false,
            $cache = false,
            $profiler = false,
            $connection = false,
            $db;

    /**
     * Initialize Doctrine manager if needed and create a connection
     * @param string $db Database identifier in config file
     */
    public function __construct ( $db = null, $autoconfigure = true )
    {
        // If database name is not set, default it to controller name
        if ( $db == null )
        {
            $db = X::get ( 'controller' ) -> __toString ();
        }
        $this -> db = $db;
        $dsn = X::get ( 'config', 'database', $db, 'dsn' );

        $this -> manager = &X::get_reference ( 'framework', 'databases', $db, 'manager' );
        $this -> cache = &X::get_reference ( 'framework', 'databases', $db, 'cache' );
        $this -> profiler = &X::get_reference ( 'framework', 'databases', $db, 'profiler' );

        // Initialize Doctrine if it hasn't been yet initialized
        if ( X::get ( 'framework', 'databases', $db, 'manager' ) === null )
        {
            $include = 'plugins/doctrine/Doctrine.php';
            if ( file_exists ( XT_FRAMEWORK_DIR .'/'. $include ) )
            {
                include ( XT_FRAMEWORK_DIR . '/'. $include );
            }
            else
            {
                include ( XT_PROJECT_DIR .'/'. $include );
            }

            spl_autoload_register ( 'Doctrine_Core::modelsAutoload' );

            // Get the manager instance
            $this -> manager = Doctrine_Manager::getInstance ();

            // Set up cache instance
            $this -> cache = new xt_doctrine_cache ();

            // Initialize profiler if needed
            if ( X::get ( 'env' ) == 'dev' )
            {
                $this -> profiler = new Doctrine_Connection_Profiler();
            }

            // Pre-configure Doctrine
            if ( $autoconfigure ) $this -> configure ();
        }

        $this -> connection = $this -> manager -> connection ( $dsn, $db );
        $this -> connection
                -> setAttribute ( Doctrine_Core::ATTR_MODEL_CLASS_PREFIX, $db .'_' );

        if ( $this -> profiler != false )
        {
            $this -> connection -> setListener ( $this -> profiler );
        }

        return $this -> connection;
    }


    public function __destruct ()
    {
        if ( $this -> connection ) $this -> connection -> flush ();
    }

    /**
     * Return Doctrine manager
     */
    public function &get_manager ()
    {
        return $this -> manager;
    }


    /**
     * Return Doctrine connection
     */
    public function &get_connection ()
    {
        return $this -> connection;
    }


    /**
     * Set up default configuration
     */
    private function configure ()
    {
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_HYDRATE_OVERWRITE, false );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_CASCADE_SAVES, false );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES, true );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true );

        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_QUERY_CACHE, $this -> cache );
        $this -> manager
                -> setAttribute ( Doctrine_Core::ATTR_RESULT_CACHE, $this -> cache );

        $models_dir = XT_PROJECT_DIR .'/models/doctrine/'. $this -> db;
        if ( is_dir ( $models_dir ) )
        {
            Doctrine_Core::loadModels ( $models_dir );
        }
    }

}


class xt_doctrine_cache extends Doctrine_Cache_Driver
{

    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    protected function _doFetch($id, $testCacheValidity = true)
    {
        return X_cache::get ( $id );
    }

    protected function _doContains($id)
    {
        return (bool)X_cache::get ( $id );
    }

    protected function _doSave($id, $data, $lifeTime = false)
    {
        X_cache::set ( $id, $data, $lifeTime );
    }

    protected function _doDelete($id)
    {
        X_cache::delete ( $id );
    }

    protected function _getCacheKeys()
    {
        return X_cache::keys ();
    }
}