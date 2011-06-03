<?php

class guestbook_model extends model_base
{

    public function __construct ()
    {
    }


    public function get_entries ()
    {
        $entries = new axon ( 'entries', 'guestbook' );
        return $entries -> lookup ( 'id, timestamp, author, message',
                             null, null, 'timestamp DESC' );
    }


    public function post ()
    {
        $entry = new axon ( 'entries', 'guestbook' );
        $entry -> copy_from ( $_POST );
        $entry -> timestamp = time ();
        $entry -> save ();
    }

}