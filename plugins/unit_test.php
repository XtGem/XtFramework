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

class unit_test
{

    private
            $executed = 0,
            $target = 0,
            $passed = 0,
            $failed = 0,
            $skipped = 0;


    /**
     * Pass constructor a number of tests to run
     * @param integer $tests
     */
    public function __construct ( $tests )
    {
        $this -> target = $tests;
    }


    /**
     * Output the result if all tests are called to be executed
     * or display a notice if too many/less tests were run
     */
    public function __destruct ()
    {
        if ( X::is_set ( 'framework', 'unit_test', 'run_all' ) )
        {
            $file = X::get ( 'framework', 'unit_test', 'current' ) .":\n";
            $message = "\tPassed ". $this -> passed .'/'. $this -> target;

            if ( $this -> skipped > 0 )
            {
                $message .= ' (skipped '. $this -> skipped .')';
            }
            
            if ( $this -> passed == $this -> executed &&
                 $this -> executed == $this -> target )
            {
                echo "\033[1;32m > ". $file ."\033[0;32m". $message ."\033[1;37m\n";
                X::set ( 'framework', 'unit_test', 'last_passed', true );
            }
            else
            {
                echo "\033[1;31m ! ". $file ."\033[0;31m". $message ."\033[1;37m\n";
                X::set ( 'framework', 'unit_test', 'last_passed', false );
            }
        }
        elseif ( $this -> executed != $this -> target )
        {
            $this -> note ( 'Warning: '. $this -> target .
                            ' tests were set to be executed, but instead '.
                            $this -> executed .' were');
        }
    }


    /**
     * Display a success message
     * @param string $message
     * @return bool
     */
    public function pass ( $message )
    {
        if ( X::is_set ( 'framework', 'unit_test', 'run_all' ) ) return true;
        echo "\033[0;32m > ". $message ."\033[1;37m\n";
        return true;
    }

    /**
     * Display a failure message
     * @param string $message
     * @return bool
     */
    public function fail ( $message )
    {
        if ( X::is_set ( 'framework', 'unit_test', 'run_all' ) ) return false;
        echo "\033[0;31m ! ". $message ."\033[1;37m\n";
        return false;
    }

    /**
     * Display a notice
     * @param string $message
     */
    public function note ( $message )
    {
        if ( X::is_set ( 'framework', 'unit_test', 'run_all' ) ) return;
        echo "\033[1;33m # ". $message ."\033[1;37m\n";
    }

    /**
     * Output a debug message (can span over multiple lines)
     * @param $string $message
     */
    public function debug ( $message, $hl = false )
    {
        if ( X::is_set ( 'framework', 'unit_test', 'run_all' ) ) return;

        if ( is_array ( $message ) || is_object ( $message ) )
        {
            $message = print_r ( $message, 1 );
        }
        if ( is_bool ( $message ) )
        {
            $message = '(bool) '. ( $message ? 'true' : 'false' );
        }

        $message = explode ( "\n", trim ( $message ) );
        foreach ( $message as $line )
        {
            echo "\t\033[". ($hl?1:0) .";36m| ". $line ."\033[1;37m\n";
        }
    }

    /**
     * Test if value1 is equal (==) to the value2
     * @param mixed $value1
     * @param mixed $value2
     * @param string $message
     * @return bool
     */
    public function is ( $value1, $value2, $message )
    {
        $pass = ( $value1 == $value2 );
        
        $this -> executed++;
        $message = $this -> executed .' '.
                   ( $pass ? 'PASS' : 'FAIL' ) .
                   ( $message != null ? ":\t". $message : null );

        if ( $pass )
        {
            $this -> passed++;
            return $this -> pass ( $message );
        }
        else
        {
            $this -> failed++;
            $this -> fail ( $message );
            $this -> debug ( 'Expected:', true );
            $this -> debug ( $value2 );
            $this -> debug ( 'Got:', true );
            $this -> debug ( $value1 );
            return false;
        }
    }

    /**
     * Test if the expression evaluates to true
     * @param bool $pass Expression
     * @param string $message
     * @return bool
     */
    public function ok ( $pass, $message )
    {
        return $this -> is ( $pass, true, $message );
    }

    /**
     * Test if the expression evaluates to false
     * @param bool $pass Expression
     * @param string $message
     * @return bool
     */
    public function not ( $pass, $message )
    {
        return $this -> is ( $pass, false, $message );
    }

    /**
     * Test if value 1 is not equal (==) to value2
     * @param mixed $value1
     * @param mixed $value2
     * @param string $message
     * @return bool
     */
    public function isnt ( $value1, $value2, $message )
    {
        return $this -> is ( $value1 != $value2, true, $message );
    }

    /**
     * Test if regex matches a string
     * @param string $string
     * @param string $regex
     * @param string $message
     * @return bool
     */
    public function like ( $string, $regex, $message )
    {
        return $this -> is ( preg_match ( $regex, $string ), true, $message );
    }

    /**
     * Test if regex does not match a string
     * @param string $string
     * @param string $regex
     * @param string $message
     * @return bool
     */
    public function unlike ( $string, $regex, $message )
    {
        return $this -> is ( !preg_match ( $regex, $string ), true, $message );
    }

    /**
     * Compare values using operator
     * @param mixed $value1
     * @param string $operator
     * @param mixed $value2
     * @param string $message
     * @return bool
     */
    public function cmp ( $value1, $operator, $value2, $message )
    {
        eval ( '$condition = $value1 '. $operator .' $value2;' );
        return $this -> is ( $condition, true, $message );
    }

    /**
     * Test the type of an argument or object's class
     * @param mixed|object $variable
     * @param string $type
     * @param string $message
     * @return bool
     */
    public function is_a ( $variable, $type, $message )
    {
        return $this -> is (
                (
                    is_object ( $variable ) ?
                        get_class ( $variable )
                    :
                        gettype ( $variable )
                ), $type,
                $message );
    }

    /**
     * Check if object has a callable method
     * @param object $object
     * @param string $method
     * @param string $message
     * @return bool
     */
    public function can ( $object, $method, $message )
    {
        return $this -> is ( is_callable ( array ( $object, $method ) ), true, $message );
    }

    /**
     * Skip one or more tests
     * @param string $message
     * @param integer $tests
     */
    public function skip ( $message = null, $tests = 1 )
    {
        $this -> executed += $tests;
        $this -> skipped += $tests;
        $this -> passed++;
        $this -> note ( 'SKIP '. $tests .
                        ( $message != null ? ":\t". $message : null ) );
    }

    /**
     * Count as a successful test
     * @param string $message
     */
    public function todo ( $message )
    {
        $this -> executed++;
        $this -> passed++;
        $this -> note ( "TODO:\t". $message );
    }

}