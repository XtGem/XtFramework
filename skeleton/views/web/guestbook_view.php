<?php if ( !is_null ( $entries ) ): ?>
    <?php foreach ( $entries as $entry ): ?>
        <b><?=$entry [ 'author' ]?></b>:
        <?=htmlspecialchars ( $entry [ 'message' ] )?>
        <small>(on <?=date('Y-m-d H:i:s', $entry [ 'timestamp' ])?>)</small>
        <br/>
    <?php endforeach; ?>
<?php else: ?>
    No entries
<?php endif; ?>

<br/><br/>
<?php if ( isset ( $errors ) ) echo '<h2>Errors occured</h2>'; ?>
<form action="<?=url('guestbook')?>" method="POST">
    <input type="text" name="author" /><br/>
        <?php if ( isset ( $errors [ 'author' ] ) ) echo 'Invalid name<br/>'; ?>
    <textarea name="message"></textarea><br/>
        <?php if ( isset ( $errors [ 'message' ] ) ) echo 'Enter some text<br/>'; ?>
    <input type="submit" name="submit" />
    <?=X_validation::form_helper()?>
</form>
<br/><br/>