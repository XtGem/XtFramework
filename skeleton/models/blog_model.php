<?php

class blog_model extends model_base
{

    public function get_posts ( $tag = null )
    {
        $posts = X_cache::get ( 'blog_post_list' );
        if ( !$posts )
        {
            new xt_doctrine_connection ( 'blog' );

            $q = Doctrine_Query::create ()
                    -> from ( 'blog_Post p' )
                    -> leftJoin ( 'p.Author' )
                    -> leftJoin ( 'p.Tags t' )
                    -> orderBy ( 'created_at DESC' );

            if ( $tag != null )
            {
                $q -> where ( 't.title = ?', $tag );
            }

            $posts = $q -> execute () -> toArray ();
            X_cache::set ( 'blog_post_list', $posts );
        }

        return $posts;
    }


    public function parse_tags ( $str )
    {
        $tags_temp = explode ( ',', $str );
        $tags = array ();
        foreach ( $tags_temp as $tag )
        {
            $tag = trim ( $tag );
            if ( $tag == null ) continue;
            $tags [] = $tag;
        }
        return $tags;
    }

    
    public function post ( $author, $title, $content, $tags )
    {
        new xt_doctrine_connection ( 'blog' );

        $author = Doctrine_Core::getTable ( 'blog_Author' ) -> findOneByUsername ( $author );
        if ( !$author ) 
        {
            X_error::message ( 'Invalid author.' );
            return false;
        }

        $tags_collection = new Doctrine_Collection ( 'blog_Tag' );
        foreach ( $tags as $tag )
        {
            $tags_collection [] -> title = $tag;
        }

        $post = new blog_Post ();
        $post -> title = $title;
        $post -> content = $content;
        $post -> blog_PostTag = $tags_collection;

        return true;
    }

}