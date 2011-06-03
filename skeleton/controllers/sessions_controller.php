<?php

class sessions_controller extends controller_base
{

    public function __construct ()
    {
        $this -> session = new session ( 'crud' );
    }

    public function read ()
    {

    }

    public function create ()
    {
        if ( !X_validation::validate ( 'sessions', $errors ) )
        {
            $this -> errors = $errors;
            X::reroute ( X_routing::url ( 'sessions' ) );
            return;
        }

        $this -> session -> { $_POST [ 'key' ] } = $_POST [ 'value' ];

        X::redirect ( X_routing::url ( 'sessions' ) );
    }

    public function update ()
    {

    }

    public function delete ()
    {
        unset ( $this -> session -> { $_POST [ 'key' ] } );

        X::redirect ( X_routing::url ( 'sessions' ) );
    }

}