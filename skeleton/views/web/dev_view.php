Current environment: <?=$env?><br/>
Cache backend: <?=( isset ( $framework [ 'cache_backend' ] ) ? $framework [ 'cache_backend' ] : 'uninitialized' )?><br/>
Session backend: <?=( isset ( $framework [ 'session_backend' ] ) ? $framework [ 'session_backend' ] : 'uninitialized' )?><br/>
Generated in: <?php $php_time = xdebug_time_index(); echo $php_time?> sec
<?php if ( isset ( $framework['databases']['query_time'] ) ): ?>
    (DB: <?=$framework['databases']['query_time']?> sec (<?=$framework['databases']['query_counter']?>),
    PHP: <?=round(100*$php_time/($php_time+$framework['databases']['query_time']))?>%)
<?php endif; ?><br/>

<?php if ( isset ( $framework [ 'databases' ] ) )
    foreach ( $framework [ 'databases' ] as $dbname => $data ): ?>
    <?php
        if ( isset ( $data [ 'profiler' ] ) )
        {
            echo "<b><?=$dbname?>:</b><br/>";
            $time = 0;
            foreach ( $data [ 'profiler' ] as $event )
            {
                $time += $event -> getElapsedSecs();
                echo $event -> getName () .' '. sprintf ( "%f", $event->getElapsedSecs() ) .'<br/>';
                echo $event -> getQuery () .'<br/>';
                $params = $event->getParams();
                if( !empty ( $params ) )
                {
                    print_r($params);
                    echo '<br/>';
                }
            }
            echo '<br/>Total time: ' . $time  . '<br/>';
        }
    ?>
<?php endforeach; ?>