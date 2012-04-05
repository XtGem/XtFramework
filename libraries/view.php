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

class X_view
{

    private static
            $view = '_layout.php',
            $template = null,
            $functions = array (
                'component',
                'url',
                'translate',
            ),
            $function_regex;


    /**
     * Prepare the script environment for view parsing
     */
    public static function init ()
    {
        // Run only once
        if ( defined ( 'XT_VIEW_DIR' ) ) return;

        // Assign X:: namespace variables to global namespace
        $GLOBALS += X::get ();

        $GLOBALS [ 'model' ] = &$GLOBALS [ 'controller' ] -> model;

        if ( self::$template === null )
        {
            self::$template = $GLOBALS [ 'config' ] [ 'view' ] [ 'template' ];
        }

        define ( 'XT_VIEW_DIR', XT_PROJECT_DIR .'/views' );
        define ( 'XT_VIEW_CACHE_DIR', XT_PROJECT_DIR .'/cache/views' );

        // Check if _ajax or _error exists, revert to _layout otherwise
        if ( ( self::$view == '_ajax.php' && !file_exists ( XT_VIEW_DIR .'/'. self::$template .'/_ajax.php' ) ) ||
             ( self::$view == '_error.php' && !file_exists ( XT_VIEW_DIR .'/'. self::$template .'/_error.php' ) ) )
        {
            self::$view = '_layout.php';
        }

        // Check if cached view dir exists. If not, create it.
        if ( !is_dir ( XT_VIEW_CACHE_DIR .'/'. self::$template ) ) 
        {
            mkdir ( XT_VIEW_CACHE_DIR .'/'. self::$template, 0777, true );
        }

        // Tell the framework that it's now parsing views
        X::set ( 'framework', 'in_view', true );
    }


    /**
     * Set a view to display
     * @param $view
     */
    public static function set ( $view = false )
    {
        if ( $view == '_layout' || $view == '_layout.php' )
        {
            self::$view = '_layout.php';
        }
        elseif ( $view == '_ajax' || $view == '_ajax.php' )
        {
            self::$view = '_ajax.php';
        }
        elseif ( $view == '_error' || $view == '_error.php' )
        {
            self::$view = '_error.php';
        }
        elseif ( $view == false )
        {
            self::$view = false;
        }
        else
        {
            self::$view = $view .'_view.php';
        }
    }


    /**
     * Get a currently set view's name
     * @param $view
     */
    public static function get ()
    {
        return self::$view;
    }



    /**
     * Set or retrieve current template
     * @param string|null $set If set, sets template. Otherwise returns current.
     * @return string
     */
    public static function template ( $set = null )
    {
        if ( $set == null )
        {
            return self::$template;
        }
        else
        {
            self::$template = $set;
        }
    }


    /**
     * Parses the view PHP file and replaces all the shortcut notations into PHP code
     * @param string $path View file to be parsed, prefixed by a template name
     */
    public static function compile ( $path )
    {
        $file_path = XT_VIEW_DIR .'/'. $path;
        $cached_path = XT_VIEW_CACHE_DIR .'/'. $path;

        // Check if the uncompiled view was updated
        if ( !file_exists ( $cached_path ) ||
                filemtime ( $file_path ) > filemtime ( $cached_path ) )
        {
            $contents = file_get_contents ( $file_path );

            // Generate a regex for matching shortcut functions
            self::$function_regex = '#\s+('. implode ( '|', self::$functions ) .')\s*\($#';

            // Replace translation keys into translated values
            $contents = preg_replace_callback ( '#<(\s*[\'"].+?)>#',
                            'X_view::translation_shortcut', $contents );

            // Find the beginning of the first PHP code block
            $pos = strpos ( $contents, '<?' );
            $parsed = substr ( $contents, 0, $pos );
            $contents = substr ( $contents, $pos );
            while ( $pos !== false )
            {
                self::parse_php ( $parsed, $contents );
                $pos = strpos ( $contents, '<?' );
                $parsed .= substr ( $contents, 0, $pos );
                $contents = substr ( $contents, $pos );
            }

            // Join all that's left unparsed
            $parsed .= $contents;
            unset ( $contents );

            // Create base directory
            $cached_dir = dirname ( $cached_path );
            if ( !is_dir ( $cached_dir ) )
            {
                mkdir ( $cached_dir, 0777, true );
                chmod ( $cached_dir, 0777 );
            }

            file_put_contents ( $cached_path, $parsed );
        }
        else
        {
            return;
        }
    }


    /**
     * Parse PHP block at the beginning of $contents
     * @param string $parsed
     * @param string $contents
     */
    private static function parse_php ( &$parsed, &$contents )
    {
        // false - pointer not in string, ' or " - how is the string quoted
        $in_string = false;
        $pos = 2;
        $bracket_start_nested = array ();
        $bracket_start = 0;
        $bracket_end = 0;
        $bracket_level = 0;

        // <?= shortcut
        if ( $contents [ $pos ] == '=' )
        {
            $contents = '<?php echo '. substr ( $contents, 3 );
            $pos = 11;
        }
        // <? shorthand
        elseif ( $contents [ $pos ] == ' ' || $contents [ $pos ] == "\n" )
        {
            $contents = '<?php '. substr ( $contents, 3 );
            $pos = 6;
        }

        // Go character-by-character
        while ( 1 )
        {
            $char = $contents [ $pos ];

            switch ( $char )
            {
                case "'":
                case '"':
                        if ( !$in_string )
                        {
                            // Begins a string
                            $in_string = $char;
                        }
                        else
                        {
                            if ( $char == $in_string &&
                                $contents [ $pos -1 ] != '\\' )
                            {
                                // Ends a string
                                $in_string = false;
                            }
                        }
                    break;

                case '>':
                        if ( !$in_string && $contents [ $pos -1 ] == '?' )
                        {
                            // Ends the PHP block
                            $parsed .= substr ( $contents, 0, $pos +1 );
                            $contents = substr ( $contents, $pos +1 );
                            return;
                        }
                    break;

                case '(':
                        if ( !$in_string ) 
                        {
                            $bracket_level++;
                            $bracket_start_nested [ $bracket_level ] = $pos;
                        }
                    break;

                case ')':
                        if ( !$in_string )
                        {
                            $bracket_end = $pos;
                            $bracket_start = $bracket_start_nested [ $bracket_level ];
                            $bracket_level--;
                            // Try matching a name of the function being called
                            if ( preg_match ( self::$function_regex, substr ( $contents, 0, $bracket_start +1 ), $matches ) )
                            {
                                $arguments = trim (
                                        substr ( $contents,
                                                 $bracket_start +1,
                                                 $bracket_end -$bracket_start -1 )
                                                  );

                                $res = ' '. call_user_func ( 'X_view::func_'. $matches [ 1 ], $arguments );

                                // Expand the shortcut function
                                $contents =
                                    // Content before function call
                                    substr ( $contents, 0, $bracket_start - strlen ( $matches [ 0 ] ) +1 ) .
                                    // Expanded content
                                    $res .
                                    // Content after function call
                                    substr ( $contents, $pos +1 );

                                // Adjust the position pointer
                                $pos +=
                                    strlen ( $res ) -
                                    strlen ( $matches [ 0 ] ) -
                                    $bracket_end + $bracket_start;
                            }
                        }
                    break;
            }

            $pos++;
        }
    }


    /**
     * Parse <'key'>, <"key">, <'key', arg, arg2> etc into X_locale::translate
     * @param array $matches
     */
    private static function translation_shortcut ( $matches )
    {
        return '<?php echo '. self::func_translate ( $matches [ 1 ] ) .'?>';
    }



    private static function func_component ( $args )
    {
        // @todo Fix this mess!
        if ( strtolower ( substr ( ltrim ( $args, '\'" ' ), 0, 9 ) ) == 'template:' )
        {
            $filename = explode ( ':', $args, 2 );
            $filename = $filename [ 1 ];
            $pos = strpos ( $filename, '/' );
            if ( $pos === false )
            {
                $template = '"'. rtrim ( $filename, '\'" ' ) .'"';
                $filename = '\'_layout.php\'';
            }
            else
            {
                $template = substr ( $filename, 0, $pos );
                $filename = rtrim ( substr ( $filename, $pos +1 ), '\'" ' );

                if ( $filename == '_layout' || $filename == null ) $filename = '"_layout.php"';
                elseif ( $filename == '_ajax' ) $filename = '"_ajax.php"';
                elseif ( $filename == '_error' ) $filename = '"_error.php"';
                else $filename = '"'. $filename ."\".'_view.php'";

                $template = '"'. $template .'"';
            }
        }
        else
        {
            $template = '\''. self::$template .'\'';
            $filename = $args .".'_view.php'";
        }

        return "X_view::compile(". $template .".'/'.". $filename ."); include(XT_VIEW_CACHE_DIR.'/'.". $template .".'/'.". $filename .")";
    }

    private static function func_url ( $args )
    {
        return 'X_routing::url('. $args .')';
    }

    private static function func_translate ( $args )
    {
        return 'X_locale::translate('. $args .')';
    }

}