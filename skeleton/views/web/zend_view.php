<b>Twitter trends for <?=$twitterTrends [ 'as_of' ]?></b>:<br/>
<?php foreach ( $twitterTrends [ 'trends' ] as $trend ): ?>
    <?='<a href="'. $trend[ 'url' ] .'">'. $trend[  'name' ] .'</a><br/>';?>
<?php endforeach; ?>