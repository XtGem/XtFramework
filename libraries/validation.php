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

class X_validation
{

    private static $config = array ();


    /**
     * Read forms configuration file
     */
    private static function init ()
    {
        if ( empty ( self::$config ) )
        {
            self::$config = X_yaml::parse ( 'config/validation.yml' );

            if ( self::$config [ 'xsrf_protection' ] == null )
            {
                self::$config [ 'xsrf_protection' ] = false;
            }
        }
    }


    /**
     * Generate a XSRF token
     * @return string XSRF token
     */
    public static function get_xsrf_token ()
    {
        self::init ();
        
        return md5 ( self::$config [ 'xsrf_protection' ] . $_SERVER [ 'REMOTE_ADDR' ] );
    }


    /**
     * Validate GET/POST vars against the form validators
     * @param string $form Form name
     * @param array $errors Passed by reference. Contains a list of failed validators.
     * @return bool Whether array validates
     */
    public static function validate ( $form, &$errors = array () )
    {
        self::init ();

        if ( !is_array ( self::$config [ 'forms' ] [ $form ] [ 'method' ] ) )
        {
            // Convert into an array
            self::$config [ 'forms' ] [ $form ] [ 'method' ] =
                array (  self::$config [ 'forms' ] [ $form ] [ 'method' ] );
        }

        // Loop through form supported methods
        foreach ( self::$config [ 'forms' ] [ $form ] [ 'method' ] as $method )
        {
            if ( $_SERVER [ 'REQUEST_METHOD' ] == $method )
            {
                $method_chk = '_'. ( $method == 'GET' ? 'GET' : 'POST' );
                $req = & $GLOBALS [ $method_chk ];
                break;
            }
        }
        // Invalid method
        if ( !isset ( $req ) ) 
        {
            $errors [ '_method' ] = false;
            return false;
        }

        // Check for XSRF token first
        if ( self::$config [ 'xsrf_protection' ] )
        {
            if ( !isset ( $req [ '__token' ] ) ||
                 $req [ '__token' ] != self::get_xsrf_token() )
            {
                $errors [ '_xsrf' ] = false;
                return false;
            }
        }

        $form_fields = array ();

        // Iterate through elements and validate them
        foreach ( self::$config [ 'forms' ] [ $form ] [ 'fields' ] as $name => $validators )
        {
            if ( !isset ( $req [ $name ] ) )
            {
                $errors [ $name ] [] = '_undefined_field';
                continue;
            }

            $form_fields [ $name ] = $req [ $name ];

            if ( $validators == null ) continue;

            if ( !is_array ( $validators ) ) $validators = array ( $validators );

            // Loop through element validators
            foreach ( $validators as $validator )
            {
                $validation_failed = false;
                
                // Loop through validator rules
                foreach ( self::$config [ 'validators' ] [ $validator ] as $rule => $value )
                {
                    switch ( $rule )
                    {
                        case 'regex':
                                if ( !preg_match ( $value, $req [ $name ] ) )
                                    $validation_failed = true;
                            break;
                            
                        case 'length':
                                $strlen = strlen ( $req [ $name ] );
                                if ( strpos ( $value, '-' ) !== false )
                                {
                                    $len = explode ( '-', $value );
                                    if ( $strlen < $len [ 0 ] || $strlen > $len [ 1 ] )
                                        $validation_failed = true;
                                }
                                else
                                {
                                    if ( $strlen < $value )
                                        $validation_failed = true;
                                }
                            break;
                    }
                }
                
                if ( $validation_failed )
                    $errors [ $name ] [] = $validator;
            }
        }

        // Make sure request array only contains form variables
        if ( isset ( self::$config [ 'forms' ] [ $form ] [ 'remove_undefined' ] ) &&
             self::$config [ 'forms' ] [ $form ] [ 'remove_undefined' ] == 'true' )
        {
            $req = $form_fields;
        }

        return ( empty ( $errors ) ? true : false );
    }


    /**
     * Generate __method and __token markup
     * @param string|null $method PUT or DELETE (optional)
     * @return string markup
     */
    public static function form_helper ( $method = null )
    {
        self::init ();
        
        if ( !self::$config [ 'xsrf_protection' ] && $method == null )
            return null;
        
        // Try to retrieve a template-specific widget first
        $template = X_view::template ();
        if ( isset ( self::$config [ 'widgets' ] [ $template ] ) &&
             is_array ( self::$config [ 'widgets' ] [ $template ] ) )
        {
            $widget = self::$config [ 'widgets' ] [ $template ] [ 'hidden' ];
        }
        else
        {
            $widget = self::$config [ 'widgets' ] [ 'hidden' ];
        }

        $markup = null;

        // Method
        if ( $method == 'PUT' || $method == 'DELETE' )
        {
            $markup .= strtr ( $widget, array (
                    '@name'  =>  '__method',
                    '@value' =>  $method,
                ) );
        }

        // XSRF
        if ( self::$config [ 'xsrf_protection' ] )
        {
            $markup .= strtr ( $widget, array (
                    '@name'  =>  '__token',
                    '@value' =>  self::get_xsrf_token(),
                ) );
        }

        return $markup;
    }

}