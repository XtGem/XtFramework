<?php

class counter_model extends model_base
{
 
    public function hits ()
    {
        $hits = new session ( 'hits' );
        if ( !isset ( $hits -> count ) ) 
            $hits -> count = 1;
        else
            $hits -> count++;

        $mod = $hits -> count % 10;
        if ( $hits -> count >= 4 && $hits -> count <= 19 )
        {
            return $hits -> count .'th';
        }
        elseif ( $mod == 1 )
        {
            return $hits -> count .'st';
        }
        elseif ( $mod == 2 )
        {
            return $hits -> count .'nd';
        }
        elseif ( $mod == 3 )
        {
            return $hits -> count .'rd';
        }
        else
        {
            return $hits -> count .'th';
        }
    }

}