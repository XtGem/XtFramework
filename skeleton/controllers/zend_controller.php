<?php

class zend_controller extends controller_base
{

    public function __construct ()
    {
        $this -> twitterTrends = X_cache::get ( 'twitter_trends' );
        if ( !$this -> twitterTrends )
        {
            $twitterSearch = new Zend_Service_Twitter_Search ();
            $this -> twitterTrends = $twitterSearch -> trends ();
            X_cache::set ( 'twitter_trends', $this -> twitterTrends, 3600 );
        }
    }

}