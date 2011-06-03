<?php if ( $route [ 'action' ] == 'list_posts' ): ?>
    <?php foreach ( $posts as $post ): ?>

        <?php if ( isset ( $route [ 'tag' ] ) ): ?>
            <a href="<?=url( 'blog' )?>">Back to post list</a><br/>
        <?php endif; ?>

        <h1><?=$post [ 'title' ] ?></h1>
        <i>by
            <b><a href="mailto:<?=$post [ 'Author' ] [ 'email' ]?>"><?=$post [ 'Author' ] [ 'username' ] ?></a></b>
            @ <?=$post [ 'created_at' ]?>
        </i><br/>
        <small>
            <pre><?=htmlspecialchars ( $post [ 'content' ] ) ?></pre>
        </small>
        Tags:
            <?php foreach ( $post [ 'Tags' ] as $tag ): ?>
                <a href="<?=url ( 'blog', 'tag', $tag [ 'title' ] ) ?>"><?= $tag [ 'title' ] ?></a>
            <?php endforeach; ?>
        <br/>

    <?php endforeach; ?>

        <hr/>
        <a href="<?=url('blog','post') ?>">New post</a>

<?php elseif ( $route [ 'action' ] == 'post_form' ): ?>
    <?=X_form::show ( 'blog_post', url ( 'blog', 'post' ), array (
            'author'    =>  array   (   'title' =>  'Your name:'  ),
            'title'     =>  array   (   'title' =>  'Post title:'  ),
            'content'   =>  array   (   'rows'  =>  10, 'cols'  =>  50  ),
            'tags'      =>  array   (   'title' =>  'Comma-separated list of tags:<br/>'  ),
        )
    ) ?>
        
<?php elseif ( $route [ 'action' ] == 'post' ): ?>
    <?php foreach ( X_error::get_messages () as $error ): ?>
        <?=$error?><br/>
    <?php endforeach; ?>

<?php endif; ?>