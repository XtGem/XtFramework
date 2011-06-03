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

class X
{

    private static $variables = array (),
                   $models = array (),
                   $controllers = array ();

    /**
     * Starts the framework
     * @return null
     * @static
     */
    public static function init ()
    {
        // Register autoloader
        spl_autoload_register ( 'X::autoloader' );

        // Register shutdown function
        register_shutdown_function ( 'X::shutdown' );

        // Read configuration
        X_config::read ();

        // Setup environment
        self::setup ();

        // Run CLI if appropriate
        X_cli::init ();

        // Route
        if ( !self::is_set ( 'route' ) )
            X_routing::route ();

        // Default to _ajax view instead of _layout if request comes in via AJAX
        if( isset ( $_SERVER [ 'HTTP_X_REQUESTED_WITH' ] ) &&
            $_SERVER [ 'HTTP_X_REQUESTED_WITH' ] == 'XMLHttpRequest' )
        {
            X_view::set ( '_ajax' );
            define ( 'VIA_AJAX', true );
        }
        else
        {
            define ( 'VIA_AJAX', false );
        }

        // Initialize controllers
        #compiler skip start
        include ( XT_FRAMEWORK_DIR .'/base/controller_base.php' );
        #compiler skip end
        $obj = self::controller_pre ();
        self::run_controller ();
        self::controller_post ( $obj );
    }

    
    /**
     * Setup global config variables
     * @static
     */
    private static function setup ()
    {
        error_reporting ( constant ( self::get ( 'config', 'error_reporting' ) ) );
        date_default_timezone_set ( self::get ( 'config', 'timezone' ) );
        error::verbose ( self::get ( 'config', 'verbose_errors' ) == 'true' ? true : false );
        if ( self::is_set ( 'config', 'log_errors' ) )
        {
            error::error_log ( self::get ( 'config', 'log_errors' ) == 'true' ? true : false );
        }
    }


    /**
     * Run controller defined in route
     * @static
     */
    private static function run_controller ()
    {
        $route = self::get ( 'route' );

        $controller = self::controller ( $route [ 'controller' ] );
        self::set ( 'controller', $controller );

        if ( isset ( $route [ 'action' ] ) )
        {
            if ( !is_array ( $route [ 'action' ] ) )
                $route [ 'action' ] = array ( $route [ 'action' ] );

            foreach ( $route [ 'action' ] as $action )
            {
                call_user_func ( array ( $controller, $action ) );
            }
        }
    }


    /**
     * Return controller object
     * @static
     */
    public static function controller ( $name, $force_constructor = true )
    {
        if ( !isset ( self::$controllers [ $name ] ) )
        {
            #compiler skip start
            //if ( !class_exists ( 'model_base' ) )
            //{
            //    include ( XT_FRAMEWORK_DIR .'/base/model_base.php' );
            //}
            #compiler skip end

            $controller_name_append = $name .'_controller';
            include ( XT_PROJECT_DIR .'/controllers/'. $controller_name_append .'.php' );
            self::$controllers [ $name ] = new $controller_name_append ();
            self::$controllers [ $name ] -> __set_controller_name ( $name );
        }
        elseif ( $force_constructor )
        {
            call_user_func ( array ( self::$controllers [ $name ], '__construct' ) );
        }

        return self::$controllers [ $name ];
    }


    /**
     * Internally reroute the request
     * @param string $url e.g. /test
     * @param string $method GET POST PUT DELETE
     * @param string $host null to use current
     * @static
     */
    public static function reroute ( $uri, $method = 'GET', $host = null )
    {
        if ( $method == 'GET' || $method == 'POST' || $method == 'PUT' || $method == 'DELETE' )
            $_SERVER [ 'REQUEST_METHOD' ] = $method;

        if ( strpos ( $uri, '?' ) !== false )
        {
            list ( $_SERVER [ 'REQUEST_URI' ], $_SERVER [ 'QUERY_STRING' ] ) =
                        explode ( '?', $uri, 2 );

            $method_gp = ( $method == 'GET' ? 'GET' : 'POST' );

            parse_str ( $_SERVER [ 'QUERY_STRING' ], $_{ $method_gp } );
            $_REQUEST = array_merge ( $_REQUEST, $_{ $method_gp } );
        }
        else
        {
            $_SERVER [ 'REQUEST_URI' ] = $uri;
        }

        if ( $host != null )
            $_SERVER [ 'HTTP_HOST' ] = $host;

        X_routing::route ();
        self::run_controller ();
    }


    /**
     * Issue a HTTP redirect
     * @param string $url URL to redirect to
     */
    public static function redirect ( $url )
    {
        header ( 'location: '. $url );
        // Some older / buggy mobile browsers may not support 'location' HTTP header
        header ( 'refresh: 0;url='. $url );
        die ( '<html><head><meta http-equiv="refresh" content="0;url='. $url .'"></head></html>' );
    }


    /**
     * Return 'pre' method of 'null' controller
     * @return object
     */
    private static function controller_pre ()
    {
        if ( file_exists ( XT_PROJECT_DIR .'/controllers/_controller.php' ) )
        {
            include ( XT_PROJECT_DIR .'/controllers/_controller.php' );
            $obj = new _controller ();
            if ( method_exists ( $obj, 'pre' ) )
                $obj -> pre ();

            return $obj;
        }
        return null;
    }


    /**
     * Run 'post' method of 'null' controller
     * @param object $obj
     */
    private static function controller_post ( $obj )
    {
        if ( $obj == null ) return;

        if ( method_exists ( $obj, 'post' ) )
            $obj -> post ();
    }


    /**
     * Return a specified model object
     * @param string $name
     * @param mixed $arg1, $arg2, ...
     */
    public static function model ( $name )
    {
        if ( !isset ( self::$models [ $name ] ) )
        {
            #compiler skip start
            if ( !class_exists ( 'model_base' ) )
            {
                include ( XT_FRAMEWORK_DIR .'/base/model_base.php' );
            }
            #compiler skip end
            
            $model_name_append = $name .'_model';

            // Check first if class has already been declared
            if ( !class_exists ( $model_name_append ) )
                include ( XT_PROJECT_DIR .'/models/'. $model_name_append .'.php' );

            $args = func_get_args ();
            array_shift ( $args );

            if ( !empty ( $args ) )
            {
                $reflection_obj = new ReflectionClass ( $model_name_append );
                self::$models [ $name ] = $reflection_obj -> newInstanceArgs ( $args );
            }
            else
            {
                self::$models [ $name ] = new $model_name_append ();
            }
        }
        return self::$models [ $name ];
    }


    /**
     * Autoloads extended classes and plugins from project's or framework's
     * default folders
     * @param string $class Requested class
     * @return null
     * @static
     */
    public static function autoloader ( $class )
    {
        // Load framework libraries (class names begin with 'X_')
        if ( substr ( $class, 0, 2 ) == 'X_' )
        {
            $class = substr ( $class, 2 );
            include ( XT_FRAMEWORK_DIR .'/libraries/'. $class .'.php' );
            return;
        }

        // Load parent controller from project's default folder
        if ( substr ( $class, -11 ) == '_controller' )
        {
            include ( XT_PROJECT_DIR. '/controllers/'. $class. '.php' );
            return;
        }

        // Load parent model from project's default folder
        if ( substr ( $class, -6 ) == '_model' )
        {
            include ( XT_PROJECT_DIR. '/models/'. $class. '.php' );
            return;
        }

        // Load plugins
        // Framework's plugin folder
        self::load_plugin ( XT_FRAMEWORK_DIR .'/plugins', $class )
                or
        // Project's plugin folder
        self::load_plugin ( XT_PROJECT_DIR .'/plugins', $class );
    }


    /**
     * Scans for plugin in specified folder.
     * Can be loaded as $dir/$plugin.php or $dir/$plugin/$plugin.php
     * @param string $dir Plugin directory to look under
     * @param string $plugin Plugin name
     * @return bool Returns true if matching plugin was found
     * @static
     */
    private static function load_plugin ( $dir, $plugin )
    {
        // Check for zend plugins
        if ( substr ( $plugin, 0, 5 ) == 'Zend_' )
        {
            $zend_path = &self::get_reference ( 'framework', 'zend_path' );
            if ( isset ( $zend_path ) && $zend_path != $dir ) return false;
            $path = $dir .'/'. str_replace ( '_', '/', $plugin ) .'.php';
            if ( file_exists ( $path ) )
            {
                $zend_path = $dir;
                include ( $path );
                return true;
            }
            return false;
        }
        // Check for doctrine
        elseif ( substr ( $plugin, 0, 9 ) == 'Doctrine_' )
        {
            $doctrine_path = &self::get_reference ( 'framework', 'doctrine_path' );
            if ( isset ( $doctrine_path ) && $doctrine_path != $dir ) return false;
            $path = $dir .'/doctrine/'. str_replace ( '_', '/', $plugin ) .'.php';
            if ( file_exists ( $path ) )
            {
                $doctrine_path = $dir;
                include ( $path );
                return true;
            }
            return false;
        }


        // Autoload other plugins
        if ( file_exists ( $dir .'/'. $plugin .'.php' ) )
        {
            include ( $dir .'/'. $plugin .'.php' );
            return true;
        }
        elseif ( file_exists ( $dir .'/'. $plugin .'/'. $plugin .'.php' ) )
        {
            include ( $dir .'/'. $plugin .'/'. $plugin .'.php' );
            return true;
        }

        return false;
    }


    /**
     * Explicitly call all destructors so that any other registered shutdown
     * functions are called after destructors
     */
    public static function shutdown ()
    {
        foreach ( self::$controllers as &$obj ) unset ( $obj );
        foreach ( self::$models      as &$obj ) unset ( $obj );
    }


    /**
     * Assign a variable in a framework's namespace
     * @param mixed $key
     * @param mixed $value
     * @static
     */
    public static function set ()
    {
        $var = &self::$variables;
        $arguments = func_get_args ();
        $value = array_pop ( $arguments );
        foreach ( $arguments as $arg )
        {
            $var = &$var [ $arg ];
        }
        $var = $value;

        // Assign the variable to global namespace if we're parsing a view
        if ( isset ( self::$variables [ 'framework' ] [ 'in_view' ] ) )
        {
            $var = &$GLOBALS;
            foreach ( $arguments as $arg )
            {
                $var = &$var [ $arg ];
            }
            $var = $value;
        }
    }


    /**
     * Retrieve whole variable namespace or a specific key
     * @param string|null $key Key to retrieve. Null to retrieve everything.
     * @param string|null (optional) array element
     * @return mixed
     * @static
     */
    public static function get ()
    {
        $var = self::$variables;
        foreach ( func_get_args() as $arg )
        {
            $var = $var [ $arg ];
        }
        return $var;
    }


    /**
     * Retrieve whole variable namespace or a specific key and return it via reference
     * @param string|null $key Key to retrieve. Null to retrieve everything.
     * @param string|null (optional) array element
     * @return mixed
     * @static
     */
    public static function &get_reference ()
    {
        $var = &self::$variables;
        foreach ( func_get_args() as $arg )
        {
            $var = &$var [ $arg ];
        }
        return $var;
    }


    /**
     * Check if variable in framework namespace is set
     * @param string|null $key Key to retrieve. Null to retrieve everything.
     * @param string|null (optional) array element
     * @return bool
     * @static
     */
    public static function is_set ()
    {
        $var = self::$variables;
        foreach ( func_get_args() as $arg )
        {
            if ( !isset ( $var [ $arg ] ) ) return false;
            $var = $var [ $arg ];
        }
        return true;
    }


    /**
     * Unset a variable in framework namespace
     * @param string|null $key Key to retrieve. Null to retrieve everything.
     * @param string|null (optional) array element
     * @return bool
     * @static
     */
    public static function un_set ()
    {
        $elements = null;
        foreach ( func_get_args() as $arg )
        {
            $elements .= "['$arg']";
        }
        eval ( 'unset (self::$variables'. $elements .');' );
    }

}


#compiler include: libraries/yaml.php
#compiler include: libraries/config.php
#compiler include: libraries/cli.php
#compiler include: libraries/routing.php
#compiler include: libraries/cache.php
#compiler include: libraries/view.php
#compiler include: libraries/locale.php
#compiler include: libraries/validation.php
#compiler include: base/controller_base.php
#compiler include: base/model_base.php