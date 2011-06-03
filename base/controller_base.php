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

class controller_base
{

    private $model,
            $controller_name;


    public function __set_controller_name ( $name )
    {
        $this -> controller_name = $name;
    }

    public function & __get ( $var )
    {
        // Check if a model object was requested
        if ( $var == 'model' )
        {
            if ( !isset ( $this -> model ) ) 
            {
                $this -> model = new model_collection ();
            }
            return $this -> model;
        }
        return X::get_reference ( $var );
    }

    public function __set ( $var, $value )
    {
        X::set ( $var, $value );
    }

    public function __toString ()
    {
        return $this -> controller_name;
    }

    public function __isset ( $var )
    {
        return X::is_set ( $var );
    }

    public function __unset ( $var )
    {
        X::un_set ( $var );
    }

}

class model_collection
{
    public function __call ( $model_name, $args = null )
    {
        array_unshift ( $args, $model_name );
        return call_user_func_array ( 'X::model', $args );
    }
    
    public function __get ( $model_name )
    {
        return X::model ( $model_name );
    }
    
}