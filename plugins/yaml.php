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

class yaml
{

    /**
     * YAML file parser - Copyright (c) Bong Cosca
     */

    private static
        $path,
        $result,
        $indent,
        $anchor = false,
        $alias = false,
        $holder = '_BLOCK_',
        $groups = array (),
        $delayed = array ();


    /**
      Convert YAML file to a PHP array
      @return array
      @param $file string
      @public
      @static
     * */
    public static function load ( $file )
    {
        if ( file_exists ( $file ) )
        {
            $source = explode ( "\n", file_get_contents ( $file ) );
            foreach ( $source as $k => $v )
            {
                $source [ $k ] = rtrim ( $v, "\r" );
            }
        }

        if ( empty ( $source ) ) return array ();

        self::$path = array ();
        self::$result = array ();

        $cnt = count ( $source );

        for ( $i = 0; $i < $cnt; $i++ )
        {
            $line = $source [ $i ];
            self::$indent = strlen ( $line ) - strlen ( ltrim ( $line ) );

            // Get parent path by indent
            if ( self::$indent == 0 )
            {
                $temp_path = array ( );
            }
            else
            {
                $temp_path = self::$path;
                do
                {
                    end ( $temp_path );
                    $lastIndent = key ( $temp_path );
                    if ( self::$indent <= $lastIndent )
                    {
                        array_pop ( $temp_path );
                    }
                } while ( self::$indent <= $lastIndent );
            }

            $line = self::strip_indent ( $line, self::$indent );

            // Ignore comments and empty lines
            if ( $line && ($line[ 0 ] == '#' ||
                    trim ( $line, " \r\n\t" ) == '---') || trim ( $line ) == '' )
                continue;

            self::$path = $temp_path;

            $last_char = substr ( trim ( $line ), -1 );
            // HTML tags should not be counted as literal blocks
            if ( preg_match ( '#<.*?>$#', $line ) ||
                    $last_char != '>' && $last_char != '|' )
            {
                $style = false;
            }
            else
            {
                $style = $last_char;
            }

            if ( $style )
            {
                $line = rtrim ( $line, $style ." \n" );
                $block = null;
                $line .= self::$holder;
                while ( ++$i < $cnt &&
                        (!trim ( $source[ $i ] ) ||
                        (strlen ( $source[ $i ] ) -
                        strlen ( ltrim ( $source[ $i ] ) )) > self::$indent)
                    )
                {
                    // Add line
                    $line = $source[ $i ];
                    $line = self::strip_indent ( $line );
                    $line = rtrim ( $line, "\r\n\t " ) ."\n";

                    if ( $style == '|' )
                        $block = $block . $line;
                    elseif ( strlen ( $line ) == 0 )
                        $block = rtrim ( $block, ' ' ) ."\n";
                    elseif ( $line == "\n" && $style == '>' )
                        $block = rtrim ( $block, " \t" ) ."\n";
                    elseif ( $line != "\n" )
                        $line = trim ( $line, "\r\n " ) .' ';

                    $block = $block . $line;
                }
                $i--;
            }

            while ( ++$i < $cnt && strlen ( $line ) && substr ( $line, -1 ) != ']' &&
                    ($line[ 0 ] == '[' || preg_match ( '#^[^:]+?:\s*\[#', $line ))
                  )
            {
                $line = rtrim ( $line, " \n\t\r" ) . ' ' . ltrim ( $source[ $i ], " \t" );
            }

            $i--;

            if ( strpos ( $line, '#' ) &&
                    strpos ( $line, '"' ) === false && strpos ( $line, "'" ) === false )
            {
                $line = preg_replace ( '/\s+#(.+)$/', '', $line );
            }

            if ( !$line ) return array ();
            $line = trim ( $line );
            if ( !$line ) return array ();
            $array = array ();
            $ref = 'A-z0-9_\-';

            if ( is_int ( strpos ( $line, '&' ) ) && $line[ 0 ] == '&' &&
                    preg_match ( '/^(&[' . $ref . ']+)/', $line, $matches ) ||
                    is_int ( strpos ( $line, '*' ) ) && $line[ 0 ] == '*' &&
                    preg_match ( '/^([\*&][' . $ref . ']+)/', $line, $matches ) ||
                    preg_match ( '#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $matches ) )
            {
                $group = $matches[ 1 ];
                if ( $group[ 0 ] == '&' ) self::$anchor = substr ( $group, 1 );
                if ( $group[ 0 ] == '*' ) self::$alias = substr ( $group, 1 );
                $line = self::stripGroup ( $line, $group );
            }

            if ( $line[ 0 ] == '-' && substr ( $line, -1, 1 ) == ':' )
            {
                // Mapped sequence
                $array = array ();
                $key = self::unquote ( trim ( substr ( $line, 1, -1 ) ) );
                $array [ $key ] = array ( );
                self::$delayed = array ( strpos ( $line, $key ) + self::$indent => $key );
                $line_array = array ( $array );
            }
            elseif ( substr ( $line, -1, 1 ) == ':' )
            {
                // Mapped value
                $line_array = array ( );
                $key = self::unquote ( trim ( substr ( $line, 0, -1 ) ) );
                $line_array [ $key ] = '';
            }
            elseif ( $line && $line[ 0 ] == '-' &&
                    (strlen ( $line ) < 4 || substr ( $line, 0, 3 ) != '---') )
            {
                // Array element
                if ( strlen ( $line ) <= 1 )
                {
                    $line_array = array ( array () );
                }
                else
                {
                    $array = array ();
                    $value = trim ( substr ( $line, 1 ) );
                    $value = self::to_type ( $value );
                    $array [] = $value;
                    $line_array = $array;
                }
            }
            elseif ( $line[ 0 ] == '[' && substr ( $line, -1, 1 ) == ']' )
            {
                // Plain array
                $line_array = self::to_type ( $line );
            }
            else
            {
                // Key/value pair
                $line_array = array ();
                $key = null;
                if ( strpos ( $line, ':' ) )
                {
                    if ( ( $line[ 0 ] == '"' || $line[ 0 ] == "'" ) &&
                            preg_match (
                                    '/^(["\'](.*)["\'](\s)*:)/', $line, $matches ) )
                    {
                        $value = trim ( str_replace ( $matches[ 1 ], '', $line ) );
                        $key = $matches [ 2 ];
                    }
                    else
                    {
                        // Get key and value
                        $explode = explode ( ':', $line );
                        $key = trim ( $explode[ 0 ] );
                        array_shift ( $explode );
                        $value = trim ( implode ( ':', $explode ) );
                    }
                    // Set type
                    $value = self::to_type ( $value );
                    if ( $key === '0' ) $key = '__!YAMLZero';
                    $line_array [ $key ] = $value;
                }
                else
                {
                    $line_array = array ( $line );
                }
            }

            if ( $style ) $line_array = self::revert ( $line_array, $block );
            self::add_array ( $line_array, self::$indent );
            foreach ( self::$delayed as $indent => $delayed )
            {
                self::$path [ $indent ] = $delayed;
            }
            self::$delayed = array ();
        }

        return self::$result;
    }


    /**
      Find type of the passed value and return value as new type
      @return mixed
      @param string $value
      @private
      @static
     * */
    private static function to_type ( $value )
    {
        if ( $value === '' ) return null;

        $first = $value [ 0 ];
        $last = substr ( $value, -1, 1 );
        $is_quoted = false;

        do
        {
            if ( !$value || $first != '"' && $first != "'" ||
                    $last != '"' && $last != "'" )
                break;
            $is_quoted = true;
        } while ( false );

        if ( $is_quoted )
        {
            return strtr (
                    substr ( $value, 1, -1 ),
                    array ( '\\"' => '"', '\'\'' => '\'', '\\\'' => '\'' )
            );
        }

        if ( strpos ( $value, ' #' ) !== false )
        {
            $value = preg_replace ( '/\s+#(.+)$/', '', $value );
        }

        if ( $first == '[' && $last == ']' )
        {
            // Take out strings sequences and mappings
            $inner_value = trim ( substr ( $value, 1, -1 ) );
            if ( $inner_value === '' ) return array ();
            $explode = self::escape ( $inner_value );
            // Propagate value array
            $value = array ();
            foreach ( $explode as $v ) $value [] = self::to_type ( $v );
            return $value;
        }

        if ( strpos ( $value, ': ' ) !== false && $first != '{' )
        {
            $array = explode ( ': ', $value );
            $key = trim ( $array[ 0 ] );
            array_shift ( $array );
            $value = trim ( implode ( ': ', $array ) );
            $value = self::to_type ( $value );
            return array ( $key => $value );
        }

        if ( $first == '{' && $last == '}' )
        {
            $inner_value = trim ( substr ( $value, 1, -1 ) );
            if ( $inner_value === '' ) return array ();
            // Inline Mapping
            // Take out strings sequences and mappings
            $explode = self::escape ( $inner_value );
            // Propagate value array
            $array = array ();
            foreach ( $explode as $v )
            {
                $sub_arr = self::to_type ( $v );
                if ( empty ( $sub_arr ) ) continue;
                if ( is_array ( $sub_arr ) )
                {
                    $array [ key ( $sub_arr ) ] = $sub_arr [ key ( $sub_arr ) ];
                    continue;
                }
                $array [] = $sub_arr;
            }
            return $array;
        }

        if ( intval ( $first ) > 0 && preg_match ( '/^[1-9]+[0-9]*$/', $value ) )
        {
            $intvalue = (int)$value;
            if ( $intvalue != PHP_INT_MAX ) $value = $intvalue;
            return $value;
        }

        if ( is_numeric ( $value ) )
        {
            if ( $value === '0' ) return 0;
            if ( trim ( $value, 0 ) === $value ) $value = (float)$value;
            return $value;
        }

        return $value;
    }


    /**
      Depth-check for more inlines or quoted strings
      @return array
      @private
      @static
     * */
    private static function escape ( $inline )
    {
        $seqs = array ();
        $maps = array ();
        $saved_strings = array ( );
        // Check for strings
        $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';

        if ( preg_match_all ( $regex, $inline, $strings ) )
        {
            $saved_strings = $strings[ 0 ];
            $inline = preg_replace ( $regex, 'YAMLString', $inline );
        }

        unset ( $regex );
        $i = 0;

        do
        {
            // Check for sequences
            while ( preg_match ( '/\[([^{}\[\]]+)\]/U', $inline, $matchseqs ) )
            {
                $seqs [] = $matchseqs [ 0 ];
                $inline = preg_replace (
                                '/\[([^{}\[\]]+)\]/U',
                                ( 'YAMLSeq' . ( count ( $seqs ) - 1 ) .'s' ), $inline, 1
                );
            }

            // Check for mappings
            while ( preg_match ( '/{([^\[\]{}]+)}/U', $inline, $matchmaps ) )
            {
                $maps [] = $matchmaps[ 0 ];
                $inline = preg_replace (
                                '/{([^\[\]{}]+)}/U',
                                ('YAMLMap' . ( count ( $maps ) - 1 ) .'s' ), $inline, 1
                );
            }

            if ( $i++ >= 10 ) break;
        } while ( is_int ( strpos ( $inline, '[' ) ) || is_int ( strpos ( $inline, '{' ) ) );

        $explode = explode ( ',', $inline );
        $stringi = 0;
        $i = 0;

        while ( true )
        {
            // Re-add the sequences
            if ( !empty ( $seqs ) )
            {
                foreach ( $explode as $key => $value )
                {
                    if ( strpos ( $value, 'YAMLSeq' ) !== false )
                    {
                        foreach ( $seqs as $seqk => $seq )
                        {
                            $explode[ $key ] = str_replace (
                                            ( 'YAMLSeq' . $seqk .'s' ), $seq, $value
                            );
                            $value = $explode[ $key ];
                        }
                    }
                }
            }

            // Re-add the mappings
            if ( !empty ( $maps ) )
            {
                foreach ( $explode as $key => $value )
                {
                    if ( strpos ( $value, 'YAMLMap' ) !== false )
                    {
                        foreach ( $maps as $mapk => $map )
                        {
                            $explode[ $key ] = str_replace (
                                            ( 'YAMLMap' . $mapk .'s' ), $map, $value
                            );
                            $value = $explode[ $key ];
                        }
                    }
                }
            }

            // Re-add the strings
            if ( !empty ( $saved_strings ) )
            {
                foreach ( $explode as $key => $value )
                {
                    while ( strpos ( $value, 'YAMLString' ) !== false )
                    {
                        $explode [ $key ] = preg_replace (
                                        '/YAMLString/', $saved_strings[ $stringi ], $value, 1
                        );
                        unset ( $saved_strings [ $stringi ] );
                        ++$stringi;
                        $value = $explode [ $key ];
                    }
                }
            }

            $finished = true;

            foreach ( $explode as $key => $value )
                if ( strpos ( $value, 'YAMLSeq' ) !== false )
                    $finished = false;
            break;
            if ( strpos ( $value, 'YAMLMap' ) !== false )
                $finished = false;
            break;
            if ( strpos ( $value, 'YAMLString' ) !== false )
                $finished = false;
            break;
            if ( $finished )
                break;
            $i++;
            if ( $i > 10 )
                break;
        }

        return $explode;
    }


    /**
      Recursive array insertion
      @param $incoming_data array
      @param $incoming_indent
      @private
      @static
     * */
    private static function add_array ( $incoming_data, $incoming_indent )
    {
        if ( count ( $incoming_data ) > 1 )
        {
            if ( empty ( $incoming_data ) ) return false;

            foreach ( $incoming_data as $k => $v )
            {
                self::add_array ( array ( $k => $v ), $incoming_indent );
            }

            return true;
        }

        $key = key ( $incoming_data );
        $value = ( isset ( $incoming_data [ $key ] ) ? $incoming_data[ $key ] : null );

        if ( $key === '__!YAMLZero' ) $key = '0';

        if ( $incoming_indent == 0 && !self::$alias && !self::$anchor )
        {
            if ( $key || $key === '' || $key === '0' )
            {
                self::$result [ $key ] = $value;
            }
            else
            {
                self::$result[ ] = $value;
                end ( self::$result );
                $key = key ( self::$result );
            }

            self::$path [ $incoming_indent ] = $key;
            return;
        }

        $history = array ();

        // Unfolding inner array tree
        $history[ ] = $array = self::$result;

        foreach ( self::$path as $k ) $history[ ] = $array = $array [ $k ];

        if ( self::$alias )
        {
            do
            {
                if ( !isset ( self::$groups [ self::$alias ] ) )
                {
                    throw new error ( "Invalid: self::$alias." );
                    break;
                }
                $groupPath = self::$groups [ self::$alias ];
                $value = self::$result;
                foreach ( $groupPath as $k )
                    $value = $value [ $k ];
            }
            while ( false );

            self::$alias = false;
        }

        if ( is_string ( $key ) && $key == '<<' )
        {
            if ( !is_array ( $array ) ) $array = array ( );
            $array = array_merge ( $array, $value );
        } 
        elseif ( $key || $key === '' || $key === '0' )
        {
            $array [ $key ] = $value;
        }
        else
        {
            if ( !is_array ( $array ) )
            {
                $array = array ( $value );
                $key = 0;
            }
            else
            {
                $array[ ] = $value;
                end ( $array );
                $key = key ( $array );
            }
        }

        $reverse_path = array_reverse ( self::$path );
        $reverse_history = array_reverse ( $history );
        $reverse_history [ 0 ] = $array;
        $cnt = count ( $reverse_history ) -1;

        for ( $i = 0; $i < $cnt; $i++ )
        {
            $reverse_history [ $i +1 ] [ $reverse_path [ $i ] ] = $reverse_history [ $i ];
        }

        self::$result = $reverse_history [ $cnt ];
        self::$path [ $incoming_indent ] = $key;

        if ( self::$anchor )
        {
            self::$groups [ self::$anchor ] = self::$path;
            if ( is_array ( $value ) )
            {
                $k = key ( $value );
                if ( !is_int ( $k ) )
                {
                    self::$groups [ self::$anchor ] [ $incoming_indent + 2 ] = $k;
                }
            }
            self::$anchor = false;
        }
    }


    /**
      Recursive revert
      @private
      @static
     * */
    private static function revert ( $line_array, $block )
    {
        foreach ( $line_array as $k => $v )
        {
            if ( is_array ( $v ) )
                $line_array[ $k ] = self::revert ( $v, $block );
            elseif ( substr ( $v, -1 * strlen ( self::$holder ) ) == self::$holder )
                $line_array[ $k ] = rtrim ( $block, " \r\n" );
        }

        return $line_array;
    }

    private static function strip_indent ( $line, $indent=-1 )
    {
        if ( $indent == -1 ) $indent = strlen ( $line ) - strlen ( ltrim ( $line ) );
        return substr ( $line, $indent );
    }

    private static function unquote ( $value )
    {
        if ( !$value )
            return $value;
        if ( !is_string ( $value ) )
            return $value;
        if ( $value[ 0 ] == '\'' )
            return trim ( $value, '\'' );
        if ( $value[ 0 ] == '"' )
            return trim ( $value, '"' );
        return $value;
    }

    private static function stripGroup ( $line, $group )
    {
        $line = trim ( str_replace ( $group, null, $line ) );
        return $line;
    }

}
