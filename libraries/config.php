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

class X_config
{
    
    /**
     * Read configuration and save it into framework's singleton
     */
    public static function read ()
    {
        $config = X_yaml::parse ( 'config/global.yml' );
        $config = self::environment_parse ( $config );
        X::set ( 'config', $config );
    }


    /**
     * Overwrite configuration based on active project environment
     * @param array $config Configuration array
     * @return array
     * @static
     */
    private static function environment_parse ( $config )
    {
        $environment = $config [ 'environment' ];
        unset ( $config [ 'environment' ] );
        if ( PHP_SAPI != 'cli' )
        {
            $env = self::environment_detect ( $environment [ 'detect' ] );
        }
        else
        {
            $env = 'cli';
        }
        unset ( $environment [ 'detect' ] );

        X::set ( 'env', $env );

        // Iterate through environments and merge the configuration
        foreach ( $environment as $environment_key => $c )
        {
            if ( $environment_key == $env )
            {
                // Merge the configuration
                if ( empty ( $c ) ) break;
                foreach ( $c as $key => $value )
                {
                    $keys = explode ( '.', $key );
                    $top_key = array_shift ( $keys );
                    $config_overlay [ $top_key ] = ( empty ( $keys ) ? $value : self::append_array ( $keys, $value ) );
                    $config = self::array_merge_recursive_distinct ( $config, $config_overlay );
                }
                break;
            }
        }

        return $config;
    }


    /**
     * Detect current environment using detection rules from the configuration
     * @param array $config Configuration array
     * @return string
     * @static
     */
    private static function environment_detect ( $rules )
    {
        // Check for production lock
        if ( file_exists ( XT_PROJECT_DIR .'/'. $rules [ 'skip_detection' ] ) ) 
            return 'production';
        unset ( $rules [ 'skip_detection' ] );

        $file_path = XT_PROJECT_DIR .'/config/global.yml';
        $cached_path = XT_PROJECT_DIR .'/cache/environment.php';

        // Check if configuration file was updated since the last caching
        if ( !file_exists ( $cached_path ) ||
                filemtime ( $file_path ) > filemtime ( $cached_path ) )
        {
            // Generate a new cache
            $cached_dir = dirname ( $cached_path );
            if ( !is_dir ( $cached_dir ) ) mkdir ( $cached_dir, 0777, true );

            $php = "<?php\n";

                foreach ( $rules as $env => $r )
                {
                    $php .= "// $env\n";
                    // No 'OR' cases, simulate
                    if ( !isset ( $r [ 0 ] ) )
                    {
                        $r [ 0 ] = $r;
                    }

                    $iteration_or = 1;
                    foreach ( $r as $rule => $values )
                    {
                        // Either start a new 'if' or add an 'or'
                        $php .= ( $iteration_or == 1 ? "\tif ( ( " : " || (" );
                        $iteration_detect = 1;
                        foreach ( $values as $detect => $compare )
                        {
                            // Open a bracket or append an 'and' statement?
                            $php .= ( $iteration_detect > 1 ? ' && ' : null ) .'(';
                            switch ( $detect )
                            {
                                case 'file': $php .= 
                                    'basename($_SERVER[\'SCRIPT_FILENAME\']) == \''. $compare .'\'';
                                    break;
                                case 'client': $php .= 
                                    '$_SERVER[\'REMOTE_ADDR\'] == \''. $compare .'\'';
                                    break;
                                case 'server':
                                case 'host': $php .=
                                    '$_SERVER[\'SERVER_ADDR\'] == \''. $compare .'\''
                                        .' || $_SERVER[\'SERVER_NAME\'] == \''. $compare .'\''
                                        .' || $_SERVER[\'HTTP_HOST\'] == \''. $compare .'\'';
                                    break;
                                case 'cookie':
                                        if ( strpos ( $compare, '=' ) !== false )
                                        {
                                            list ( $cmp_name, $cmp_value ) =
                                                    explode ( '=', $compare, 2 );
                                            $php .= 'isset($_COOKIE[\''. $cmp_name .'\'])'
                                                    .' && $_COOKIE[\''. $cmp_name .'\'] == '
                                                    .' \''. $cmp_value .'\'';
                                        }
                                        else
                                        {
                                            $php .= 'isset($_COOKIE[\''. $compare .'\'])';
                                        }
                                    break;
                                case 'variable':
                                case 'env':
                                        if ( strpos ( $compare, '=' ) !== false )
                                        {
                                            list ( $cmp_name, $cmp_value ) =
                                                    explode ( '=', $compare, 2 );
                                            $php .= 'isset($_ENV[\''. $cmp_name .'\'])'
                                                    .' && $_ENV[\''. $cmp_name .'\'] == '
                                                    .' \''. $cmp_value .'\'';
                                        }
                                        else
                                        {
                                            $php .= 'isset($_ENV[\''. $compare .'\'])';
                                        }
                                    break;
                            }
                            $php .= ')';
                            $iteration_detect++;
                        }
                        $php .= ' )';
                        $iteration_or++;
                    }
                    $php .= " ) \$env='$env';\n";
                }

            file_put_contents ( $cached_path, $php );
        }

        // Run the cached environment detector
        $env = 'production';
        include ( $cached_path );
        return $env;
    }


    /**
     * Recursive function used to generate an array tree for merging with config
     * @param array $keys
     * @param mixed $value
     * @return array
     * @static
     */
    private static function append_array ( $keys, $value )
    {
        $key = array_shift ( $keys );
        if ( !empty ( $keys ) )
        {
            return array ( $key => self::append_array ( $keys, $value ) );
        }
        else
        {
            return array ( $key => $value );
        }
    }

    
    /**
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @static
     */
    private static function array_merge_recursive_distinct ( $array1, $array2 )
    {
        $merged = $array1;

        foreach ( $array2 as $key => &$value )
        {
            if ( is_array ( $value ) && isset ( $merged [ $key ] ) && is_array ( $merged [ $key ] ) )
            {
                $merged [ $key ] = self::array_merge_recursive_distinct ( $merged [ $key ], $value );
            }
            else
            {
                $merged [ $key ] = $value;
            }
        }

        return $merged;
    }

}