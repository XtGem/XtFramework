<?php

class user_controller extends controller_base
{

    public function __construct ()
    {
    }

    public function greet ()
    {
        $this -> greet = 'Hello world, '. ucfirst ( $this -> route [ 'user' ] ) .'!';
    }

    public function previous ()
    {
        // Start a session with namespace 'user'
        $session = new session ( 'user' );
        if ( isset ( $session -> previous ) )
        {
            $this -> previous = ucfirst ( $session -> previous );
            if ( $this -> route [ 'user' ] != $session -> previous )
            {
                $session -> previous = $this -> route [ 'user' ];
            }
        }
        else
        {
            $session -> previous = $this -> route [ 'user' ];
            $this -> previous = ucfirst ( $session -> previous );
        }
    }

}