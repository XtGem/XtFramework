<?php

class xsrf_controller extends controller_base
{

    public function __construct ()
    {
        $this -> session = new session ( 'xsrf' );
        if ( !isset ( $this -> session -> state ) ) $this -> session -> state = 1;
    }

    public function set ()
    {
        $this -> session -> state = $_GET [ 'state' ];
    }

}