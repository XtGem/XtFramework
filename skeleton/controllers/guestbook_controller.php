<?php

class guestbook_controller extends controller_base
{

    public function __construct ()
    {
        $this -> action = X::get ( 'route', 'action' );
    }

    public function list_entries ()
    {
        $this -> entries = $this -> model -> guestbook -> get_entries ();
    }

    public function post ()
    {
        if ( !X_validation::validate ( 'guestbook', $errors ) )
        {
            $this -> errors = $errors;
            X::reroute ( X_routing::url ( 'guestbook' ) );
            return;
        }

        $this -> model -> guestbook -> post ();

        X::redirect ( X_routing::url ( 'guestbook' ) );
    }

}