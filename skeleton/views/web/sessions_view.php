<? switch ( $route [ 'action' ] ): default: break; ?>

    <? case 'read': ?>
            <? $i = 1 ?>
            <? foreach ( $session as $key => $value ): ?>
                <?=$key?> => <?=$value?> 
                <a href="javascript:document.getElementById('key').value='<?=htmlspecialchars(str_replace('\'','\\\'',$key))?>';document.getElementById('value').value='<?=htmlspecialchars(str_replace('\'','\\\'',$value))?>';void(0)">E</a>
                <a href="javascript:document.getElementById('form_<?=$i?>').submit()">-</a>
                <form action="<?=url('sessions')?>" method="POST" id="form_<?=$i?>">
                    <input type="hidden" name="key" value="<?=$key?>" />
                    <?=X_validation::form_helper( 'DELETE' )?>
                </form>
                <br/>
            <? $i++ ?>
            <? endforeach; ?>
            <br/>
            <form action="<?=url('sessions')?>" method="POST">
                <input type="text" name="key" id="key" /> =>
                <input type="text" name="value" id="value" /><br/>
                    <?php if ( isset ( $errors [ 'key' ] ) ) echo 'Invalid key<br/>'; ?>
                    <?php if ( isset ( $errors [ 'value' ] ) ) echo 'Invalid value<br/>'; ?>
                <input type="submit" name="submit" value="Add" />
                <?=X_validation::form_helper( 'PUT' )?>
            </form>
        <? break; ?>

    <? case 'create': ?>

        <? break; ?>

    <? case 'update': ?>

        <? break; ?>

    <? case 'delete': ?>

        <? break; ?>

<? endswitch; ?>