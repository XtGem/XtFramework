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

class X_routing
{

    private static $routes = false,
                   $current_route = array (),
                   $domain_variables = array (),
                   $internal_routes = array (),
                   $internal_route_db_updated = false;
    

    /**
     * Handle routing
     * @static
     */
    public static function route ()
    {
        if ( self::$routes == false )
            self::$routes = X_yaml::parse ( 'config/routing.yml' );

        // Remove extension
         if ( self::$routes [ 'extension' ] != null )
         {
            $ext = '.'. self::$routes [ 'extension' ];
            $ext_len = strlen ( $ext );
            // URL ends with extension
            if ( substr ( $_SERVER [ 'REQUEST_URI' ], -$ext_len ) == $ext )
            {
                $_SERVER [ 'REQUEST_URI' ] = 
                    substr ( $_SERVER [ 'REQUEST_URI' ], 0, -$ext_len );
            }
            // Remove from URLs with query string
            else
            {
                $_SERVER [ 'REQUEST_URI' ] =
                    str_replace ( $ext .'?', '?', $_SERVER [ 'REQUEST_URI' ] );
            }
         }

        $route_matched = self::$routes [ 'default' ];

        foreach ( self::$routes [ 'routing' ] as $domain => $namespace )
        {
            foreach ( $namespace as $uri => $route )
            {
                // Check if domain matches
                if ( !self::match_domain ( $domain ) ) continue;

                $action = self::match_route ( $uri, $route, $route_matched );
                switch ( $action )
                {
                    case 'final':   break 3;
                }
            }
        }

        self::execute_route ( $route_matched );
    }


    /**
     * Checks is http host header matches the current routing namespace requirement
     * @param string $domain Domain name to be checked against
     * @return bool Whether it matches
     * @static
     */
    private static function match_domain ( $domain )
    {
        if ( !isset ( $_SERVER [ 'HTTP_HOST' ] ) ) return false;

        // Global namespace
        if ( $domain == '*' ) return true;

        // Wildcard or variable present in the domain name?
        if ( strpos ( $domain, '*' ) !== false ||
             strpos ( $domain, '@' ) !== false)
        {

            list ( $regex, $variable_names ) =
                    self::build_route_check_regex ( $domain, '.', true );

            if ( isset ( $_SERVER [ 'HTTP_HOST' ] ) && preg_match ( $regex, $_SERVER [ 'HTTP_HOST' ], $variables ) )
            {
                // Check if at least single argument was matched
                if ( isset ( $variables [ 1 ] ) )
                {
                    unset ( $variables [ 0 ] );
                    self::$domain_variables =
                            array_combine ( $variable_names, $variables );
                }

                // Domain was matched
                return true;
            } else return false;
        }

        // Otherwise simple comparison
        return ( strtolower ( $_SERVER [ 'HTTP_HOST' ] ) == strtolower ( $domain ) );
    }


    /**
     * Attempts to match route to specified one
     * @param string $uri Route URI
     * @param array $route Route configuration array
     * @param array $route_matched Information about route last matched
     * @static
     */
    private static function match_route ( $uri, $route, &$route_matched )
    {
        /*
         * URI = METHOD and URL (GET /file)
         * URL = Just the request URL (/file)
         */

        $method = $_SERVER [ 'REQUEST_METHOD' ];
        $path = $_SERVER [ 'REQUEST_URI' ];

        // Deal with query string
        if ( !self::config ( 'parse_query', true ) )
        {
            $query = strrpos ( $path, '?' );
            if ( $query !== false )
            {
                $path = substr ( $path, 0, $query );
            }
        }

        /*
         * Routes can be defined as:
         *  URI: controller, config
         *  URI:
         *      method: controller, config
         *      method: controller, config
         */
        self::$current_route = $route;
        self::$current_route [ 'uri' ] = $uri;

        // Check if method is specified
        if ( strpos ( self::$current_route [ 'uri' ], ' ' ) !== false )
        {
            // Extract defined route's method and URL
            list ( self::$current_route [ 'method' ],
                   self::$current_route [ 'url' ] ) =
                    explode ( ' ', self::$current_route [ 'uri' ], 2 );

            // Do not parse if method does not match
            if ( $method != self::$current_route [ 'method' ] ) return;
        }
        else
        {
            // Method not specified in URI
            self::$current_route [ 'url' ] = self::$current_route [ 'uri' ];
            
            // Check if specified as a sub-array
            if ( isset ( $route [ $method ] ) )
            {
                self::$current_route =
                        array_merge ( self::$current_route, $route [ $method ] );
            }
            else
            {
                // Otherwise check that no other method sub-array is defined
                foreach ( array ( 'GET', 'POST', 'PUT', 'DELETE' ) as $method_check )
                {
                    if ( isset ( $route [ $method_check ] ) )
                    {
                        return;
                    }
                }
            }
        }

        // Do not parse if additional routing rules do match
        if ( !self::check_arguments () ) return;

        // Compare using regex if any variables or wildcards are present
        if ( strpos ( self::$current_route [ 'url' ], '@' ) !== false ||
             strpos ( self::$current_route [ 'url' ], '*' ) !== false)
        {
            list ( $regex, $variable_names ) =
                    self::build_route_check_regex (
                                    self::$current_route [ 'url' ] );

            if ( preg_match ( $regex, $path, $variables ) )
            {
                // Check if at least single argument was matched
                if ( isset ( $variables [ 1 ] ) )
                {
                    unset ( $variables [ 0 ] );
                    $variables = array_combine ( $variable_names, $variables );
                }
                else $variables = array ();

                $route_matched = $variables + self::$current_route;
                // Stop checking routes
                if ( !self::config ( 'continue' ) ) return 'final';
            }
        }
        else
        // Otherwise simply compare strings
        {
            $check_path = $path;
            $route_check = self::$current_route [ 'url' ];
            // Case-insensitive compare
            if ( !self::config ( 'case_sensitive', true ) )
            {
                $check_path = strtolower ( $check_path );
                $route_check = strtolower ( $route_check );
            }
            if ( $check_path == $route_check )
            {
                $route_matched = self::$current_route;
                // Stop checking routes
                if ( !self::config ( 'continue' ) ) return 'final';
            }
        }
    }


    /**
     * Check additional route arguments
     * Method can be extended with custom checks
     *  function check_arguments ()
     *  {
     *      return $this -> config ( 'disabled' ) ? false : true;
     *  }
     * @return bool True if route validates against the rules
     * @static
     */
    private static function check_arguments ()
    {
        return true;
    }


    /**
     * Check if boolean routing rule matches
     * @param string $rule rule name
     * @param bool $global whether to check global route config first
     * @static
     */
    private static function config ( $rule, $global = false )
    {
        if ( isset ( self::$current_route [ $rule ] ) )
        {
            $rule = self::$current_route [ $rule ];
        }
        elseif ( $global && isset ( self::$routes [ $rule ] ) )
        {
            $rule = self::$routes [ $rule ];
        }
        else
        {
            return false;
        }
            
        return ( $rule == 'true' || $rule == 'yes' ? true : false );
    }


    /**
     * Makes a preg regex out of a provided route string
     * @param string $route Route to be parsed
     * @param string $delimiter read from config for paths, use '.' for domains
     * @param bool|null $case_insensitive determines whether the comparison is case-sensitive
     * @return array An array with the regex and a list of variable names defined
     * @static
     */
    private static function build_route_check_regex ( $route, $delimiter = null, $case_insensitive = null )
    {
        $regex = preg_quote ( $route );
        $regex = str_replace ( '\*', '.*', $regex );

        if ( $delimiter == null ) $delimiter = self::$routes [ 'separator' ];

        $variable_names = array ();
        for ( $i = 0, $max = preg_match_all ( '#(@\w+?)'. preg_quote ( $delimiter ) .'#',
                $route . $delimiter, $variable_name_matches ); $i < $max; $i++ )
        {
            $regex = str_replace ( $variable_name_matches [ 1 ] [ $i ], '([\w\-\.!~*\'"(),=]+?)', $regex );
            $variable_names [] = substr ( $variable_name_matches [ 1 ] [ $i ], 1 );
        }

        $regex = '#^'. $regex .'$#'
                    .( !self::config ( 'case_sensitive' ) ? 'i' : null );

        return array ( $regex, $variable_names );
    }


    /**
     * Run the matched route
     * @param array $config Matched route configuration
     * @static
     */
    private static function execute_route ( $config )
    {
        $route = ( isset ( self::$domain_variables ) ?
                   self::$domain_variables : array () ) + $config;
        X::set ( 'route', $route );

        // Check for XSRF if required
        if ( isset ( $route [ 'xsrf' ] ) && $route [ 'xsrf' ] == 'true' )
        {
            if ( !isset ( $_REQUEST [ '__token' ] ) ||
                 $_REQUEST [ '__token' ] != X_validation::get_xsrf_token () )
            {
                $route = self::$routes [ 'default' ];
                $route [ 'action' ] = 'HTTP_403';
                X::set ( 'route', $route );
            }
        }
    }


    /**
     * Update or create internal name => route DB
     * @static
     */
    private static function update_internal_name_db ()
    {
        $file_path = XT_PROJECT_DIR .'/config/routing.yml';
        $cached_path = XT_PROJECT_DIR .'/cache/routing_internal';

        if ( !self::$internal_route_db_updated && (
                !file_exists ( $cached_path ) ||
                filemtime ( $file_path ) > filemtime ( $cached_path ) )
           )
        {
            foreach ( self::$routes [ 'routing' ] as $domain => $namespace )
            {
                foreach ( $namespace as $uri => $route )
                {
                    // Check if method is specified in the route URI
                    if ( strpos ( $uri, ' ' ) !== false )
                    {
                        // Extract defined route's method and URL
                        list ( $method, $uri ) = explode ( ' ', $uri, 2 );
                    }
                    
                    // Check for route name
                    if ( isset ( $route [ 'route_name' ] ) )
                    {
                        self::$internal_routes [ $route [ 'route_name' ] ] = 
                            array (
                                    $uri,
                                    // XSRF flag
                                    ( isset ( $route [ 'xsrf' ] ) && $route [ 'xsrf' ] == 'true' )
                                );
                    }
                    else
                    {
                        // Check if methods sub-array is specified
                        foreach ( array ( 'GET', 'POST', 'PUT', 'DELETE' ) as $method_check )
                        {
                            if ( isset ( $route [ $method_check ] ) &&
                                 isset ( $route [ $method_check ] [ 'route_name' ] ) )
                            {
                                self::$internal_routes [ $route [ $method_check ] [ 'route_name' ] ] =
                                    array (
                                            $uri,
                                            // XSRF flag
                                            ( isset ( $route [ $method_check ] [ 'xsrf' ] ) &&
                                              $route [ $method_check ] [ 'xsrf' ] == 'true' )
                                        );
                            }
                        }
                    }
                }
            }

            // Generate a new cache
            $cached_dir = dirname ( $cached_path );
            if ( !is_dir ( $cached_dir ) )
            {
                mkdir ( $cached_dir, 0777, true );
                chmod ( $cached_dir, 0777 );
            }

            file_put_contents ( $cached_path, serialize ( self::$internal_routes ) );

            // Chmod file if it isn't already world-writeable
            $perms = substr ( decoct ( fileperms ( $cached_path ) ), -3 );
            if ( $perms != 777 ) chmod ( $cached_path, 0777 );
        }
        else
        {
            if ( empty ( self::$internal_routes ) )
            {
                self::$internal_routes = unserialize ( file_get_contents ( $cached_path ) );
            }
        }

        self::$internal_route_db_updated = true;
    }


    /**
     * Generate framework application url
     * @return string Generated URL
     * @static
     */
    public static function url ()
    {
        $url = null;
        $query = array ();

        // Load session plugin if always_initialize is enabled and it hasn't been
        // loaded yet
        if ( X::get ( 'config', 'session', 'always_initialize' ) == 'true' &&
             !X::is_set ( 'session' ) )
        {
            new session ();
        }

        // Check if session id has to be passed via URL
        if ( X::is_set ( 'framework', 'session_transport' ) &&
             X::get ( 'framework', 'session_transport' ) == 'url' )
        {
            $query [ X::get ( 'config', 'session', 'url_get' ) ] = session_id ();
        }

        $args = func_get_args ();

        // Check if first argument is an internal route name
        if ( isset ( $args [ 0 ] [ 0 ] ) && $args [ 0 ] [ 0 ] == '%' )
        {
            $route_name = substr ( $args [ 0 ], 1 );
            array_shift ( $args );

            // Update internal route name db
            self::update_internal_name_db ();

            // Check if route exists
            if ( isset ( self::$internal_routes [ $route_name ] ) )
            {
                $url = self::$internal_routes [ $route_name ] [ 0 ];
                
                // Check for argument array (must be a second argument to url() method)
                if ( isset ( $args [ 0 ] ) && is_array ( $args [ 0 ] ) )
                {
                    // Replace arguments
                    foreach ( $args [ 0 ] as $name => $value )
                    {
                        // Numeric keys correspond to wildcard arguments
                        if ( is_integer ( $name ) )
                        {
                            $pos = strpos ( $url, '*' );
                            if ( $pos !== false )
                            {
                                $url = substr ( $url, 0, $pos ) . $value . substr ( $url, $pos +1 );
                            }
                        }
                        else
                        {
                            $url = str_replace ( '@'. $name, $value, $url );
                        }
                    }

                    // Remove remaining wildcards
                    $url = str_replace ( '*', null, $url );

                    // Remove argument array from the method arguments
                    array_shift ( $args );
                }

                // Load separator setting from routing.yml if undefined (can happen in CLI environment)
                if ( self::$routes == false )
                    self::$routes = X_yaml::parse ( 'config/routing.yml' );

                // Replace / into configured argument separators
                $url = str_replace ( '/', self::$routes [ 'separator' ], $url );

                // Check for XSRF protection flag, auto-add XSRF token if needed
                if ( self::$internal_routes [ $route_name ] [ 1 ] == true )
                {
                    $query [ '__token' ] = X_validation::get_xsrf_token ();
                }
            }
            else
            {
                throw new error ( 'Internal route name %'. $route_name .' is not defined' );
            }
        }

        foreach ( $args as $arg )
        {
            // Query string
            if ( $arg != null && $arg [ 0 ] == '?' )
            {
                parse_str ( str_replace ( '&amp;', '&', ( substr ( $arg, 1 ) ) ), $parsed );
                $query = array_merge ( $query, $parsed );
                continue;
            }

            if ( !isset ( $route_name ) )
            {
                $url .= ( $arg != '/' ? self::$routes [ 'separator' ] : null ) . $arg;
            }
        }

        // Append extension
        if ( self::$routes [ 'extension' ] != null && $url != '/' && $url != null )
        {
            $url .= '.'. self::$routes [ 'extension' ];
        }

        // Append query string
        if ( !empty ( $query ) )
        {
            $ampersand = ( X::is_set ( 'framework', 'in_view' ) ? '&amp;' : '&' );
            // Auto-append XSRF token?
            if ( isset ( $query [ '_xsrf' ] ) )
            {
                unset ( $query [ '_xsrf' ] );
                $query [ '__token' ] = X_validation::get_xsrf_token ();
            }
            $url .= '?'. http_build_query ( $query, null, $ampersand );
        }

        return $url;
    }

}