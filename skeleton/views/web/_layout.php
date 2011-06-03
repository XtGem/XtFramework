<?php
    header ( 'Content-Type: text/html; charset=utf-8' );
    header ( 'Cache-Control: no-cache' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>XtFramework</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <style type="text/css">
            h2
            {
                font-size: 16px;
                color: #444;
                text-indent: 15px;
                position: relative;
                top: -14px;
            }
            #content
            {
                border: solid #999;
                border-width: 2px 0;
                border-left: 1px dashed #bbb;
                margin: 20px;
                padding: 10px;
            }
        </style>
    </head>
    <body>
        <h1><a href="<?=url('/')?>">Xt Framework</a></h1>
        <h2>This is your <?=$model->counter->hits()?> visit here</h2>
        
        <div id="content">
            <?php component ( $controller ) ?>
        </div>

        <?php if ( $env == 'dev' ) component ( 'dev' ) ?>
    </body>
</html>