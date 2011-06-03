<?php

class error_controller extends controller_base
{

    public function __construct ()
    {
        $this -> type = null;
    }

    public function HTTP_404 ()
    {
        X_view::set ( 'error' );
        $this -> type = '404';
    }

    public function HTTP_403 ()
    {
        X_view::set ( 'error' );
        $this -> type = '403';
    }

}