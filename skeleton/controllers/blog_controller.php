<?php

class blog_controller extends controller_base
{

	public function __construct ()
	{

	}


        public function list_posts ()
        {
            $tag = ( X::is_set ( 'route', 'tag' ) ? X::get ( 'route', 'tag' ) : null );
            $this -> posts = $this -> model -> blog -> get_posts ( $tag );
        }


        public function post_form ()
        {
            
        }


        public function post ()
        {
            if ( !X_form::validate ( 'blog_post' ) ) return;

            $res = $this -> model -> blog -> post (
                        $_POST [ 'author' ],
                        $_POST [ 'title' ],
                        $_POST [ 'content' ],
                        $this -> model -> blog -> parse_tags ( $_POST [ 'tags' ] )
                    );
        }
}