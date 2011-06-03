<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *   The MIT License                                                                 *
 *                                                                                   *
 *   Copyright (c) 2011 Povilas Musteikis, UAB XtGem                                 *
 *                                                                                   *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy    *
 *   of this software and associated documentation files (the "Software"), to deal   *
 *   in the Software without restriction, including without limitation the rights    *
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell       *
 *   copies of the Software, and to permit persons to whom the Software is           *
 *   furnished to do so, subject to the following conditions:                        *
 *                                                                                   *
 *    The above copyright notice and this permission notice shall be included in     *
 *   all copies or substantial portions of the Software.                             *
 *                                                                                   *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR      *
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,        *
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE     *
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER          *
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,   *
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN       *
 *   THE SOFTWARE.                                                                   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class error extends Exception
{

    const ERR_USER = 256;

    private static  $verbose = true,
                    $silent = false,
                    $previous_error_reporting = E_ALL,
                    $framework_traces = true,
                    $error_log = false,
                    $errfile = null,
                    $errline = null;


    /**
     * Handles arrays nicely. Useful for debugging
     */
    public function __construct ( $message = null, $code = 0 )
    {
        if ( is_array ( $message ) || is_object ( $message ) )
        {
            $message = '<pre>' . htmlspecialchars ( print_r ( $message, 1 ) ) . '</pre>';
        }
        parent::__construct ( $message, $code );
    }

    
    /**
     * Handle PHP errors
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    public static function php_error_handler ( $errno, $errstr, $errfile, $errline )
    {
        self::$errfile = $errfile;
        self::$errline = $errline;
        
        if ( !self::$silent && error_reporting () )
        {
            if ( defined ( 'XT_PROJECT_DIR' ) && file_exists ( XT_PROJECT_DIR .'/error_handler.php' ) )
            {
                if ( !class_exists ( '_error_handler' ) )
                { 
                    include ( XT_PROJECT_DIR .'/error_handler.php' );
                }

                $obj = new _error_handler ();

                if ( method_exists ( $obj, 'handle' ) )
                { 
                    $obj -> handle ( $errno, $errstr, $errfile, $errline );
                }
            }
            else
            {
                throw new error ( "<b>$errstr</b> (<b>#$errno</b>)" );
            }
        }
    }


    /**
     * Handles some uncaught exceptions
     */
    public static function php_exception_handler ( $e )
    {
        die ( $e -> getMessage ( ) );
    }


    /**
     * Enable to repress non-fatal errors
     */
    public static function silent ( $mode )
    {
        if ( !is_bool ( $mode ) )
            $mode = false;

        self::$silent = $mode;

        if ( $mode )
        {
            self::$previous_error_reporting = error_reporting ();
            error_reporting ( 0 );
        }
        else
        {
            error_reporting ( self::$previous_error_reporting );
        }
    }


    /**
     * Set verbose error mode
     */
    public static function verbose ( $mode )
    {
        if ( !is_bool ( $mode ) )
            $mode = false;

        self::$verbose = $mode;
    }


    /**
     * Set verbose error mode
     */
    public static function error_log ( $mode )
    {
        if ( !is_bool ( $mode ) )
            $mode = false;

        self::$error_log = $mode;
    }


    /**
     * Display the exception error message
     */
    public static function show ( $e )
    {
        if ( self::$silent )
            return null;

        $file = ( self::$errfile != null ? self::$errfile : $e -> getFile () );
        $line = ( self::$errline != null ? self::$errline : $e -> getLine () );

        // Die if run via CLI
        if ( PHP_SAPI == 'cli' )
        {
            die ( $e -> getMessage () ."\n\tin ".
                        $file .' on line '.
                        $line ."\n" );
        }

        // Log the message using system logger
        if ( self::$error_log )
        {
            error_log ( $e -> getMessage () .' in '.
                        $file .' on line '.
                        $line );
        }

        self::silent ( true );
        ob_end_clean ();
        ob_start ();

        header ( 'HTTP/1.0 500 Internal Server Error' );
        header ( 'Content-Type: text/html; charset=utf-8' );
        header ( 'Cache-Control: no-cache' );

        // User error
        if ( $e -> getCode () == self::ERR_USER )
        {
            $GLOBALS [ 'e' ] = $e;

            X_view::set ( '_error' );

            // Prepare the view environment
            X_view::init ();

            // Check if template exists
            if ( file_exists ( XT_VIEW_DIR .'/_error.php' ) )
            {
                // Update the cached view if needed
                X_view::compile ( X_view::get () );
                // Run it!
                include ( XT_VIEW_CACHE_DIR .'/'. X_view::get () );
                return;
            }
        }

        echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Error occurred</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <style type="text/css">
        body {
            font-family: arial, serif;
            margin: 0;
            padding: 0;
        }

        #holder {
            margin: 20px 40px;
        }

        h1 {
            font-size: 30px;
            font-weight: 700;
            color: #fff;
            text-shadow: #aaa 0 1px 0;
            text-align: right;
            padding: 10px 20px 5px 0;

            background: #333333;
            border-bottom: 5px solid #191919;

            -moz-border-radius-topleft: 1em;
            -webkit-border-top-left-radius: 1em;
            -moz-border-radius-topright: 1em;
            -webkit-border-top-right-radius: 1em;
            margin: 0 0 1px 0;
        }

        #message {
            background: #e8e8e8;
            float: left;
            width: 100%;
            border-top: 1px solid #dedede;
        }
            #message span.emblem {
                display: block;
                font-size: 150px;
                color: #c4c4c4;
                width: 25px;
                float: left;
                margin: 0 10px 10px 10px;
            }
            #message div#content {
                float: left;
                display: block;
                font-size: 13px;
                padding: 60px 30px 20px 30px;
            }
            #message div#content h2 {
                font-size: 13px;
                margin: 0 0 10px 0;
            }
            #message div#content .debug {
                margin-bottom: 2px;
            }
            #message div#content .debug b {
                background: #333;
                color: #fff;
            }
            #message div#content #code {
                background: #fff;
                border: 1px solid #ddd;
                margin: 0;
                width: 100%;
                padding: 5px;
                margin-top: 10px;
            }
                #message div#content #code div
                {
                    clear: both;
                    padding: 0;
                }
                #message div#content #code .hl {
                    display: block;
                    background: #f1f1f1;
                    width: 100%;
                }

        #trace {
            clear: both;
            float: left;
            background: #f1f1f1;
            margin: 2px 0 0 20px;
            padding: 20px 50px;
        }
            #trace span {
                padding-left: 10px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div id="holder">
            <h1>Error occurred</h1>

            <div id="message">
                <span class="emblem">{</span>
                <div id="content">
                    <h2>'. $e -> getMessage () .'</h2>';
                    if ( self::$verbose )
                    {
                        echo '
                        <div class="debug"><b>Line:</b> '. $line .'</div>
                        <div class="debug"><b>File:</b> '. $file .'</div>';

                        $code = file ( $file );
                        $code_dump = array ();
                        for ( $i = $line -4, $max = $line +3; $i < $max; $i++ )
                        {
                            if ( isset ( $code [ $i ] ) )
                                $code_dump [ $i +1 ] = $code [ $i ];
                        }

                        $code = null;
                        foreach ( $code_dump as $l => $str )
                        {
                            if ( $l != $line ) 
                            {
                                $code .= '<div>'. self::highlight ( $str ) ."</div>\n";
                            }
                            else
                            {
                                $code .= '<div class="hl">'. self::highlight ( $str ) ."</div>\n";
                            }
                        }
                        $code = '<div id="code">'. $code .'</div>';

                        echo $code;
                    }
                    echo '
                </div>
            </div>
            ';
            if ( self::$verbose )
            {
            echo '
                <div id="trace">';

                $trace = $e -> getTrace ( );

                foreach ( $trace as $t )
                {
                    if ( $t [ 'file' ] == null ) continue;
                    
                    if ( !self::$framework_traces )
                    {
                        // Skip framework stack traces
                        if ( preg_match ( '#^'. preg_quote ( XT_FRAMEWORK_DIR ) .'/#', $t [ 'file' ] ) )
                                continue;
                    }

                    $str = $t [ 'file' ] .':'. $t [ 'line' ] .'<br/>'.
                           '<span>'.
                                $t [ 'class' ] .' '. $t [ 'type' ] .' '.
                                $t [ 'function' ] .' ( '. implode ( ', ', $t [ 'args' ] ) .' ) '.
                           "</span><br/>\n";
                    echo $str;
                }

            echo '
                </div>';
            }
            echo '
        </div>
    </body>
</html>';
    }


    /**
     * Do syntax highlighting for current line
     * @param string $code
     * @return string
     */
    private function highlight ( $code )
    {
        if ( strlen ( $code ) > 500 ) $code = substr ( $code, 0, 500 ) .'.....';
        $code = '<?php '. $code;
        $code = highlight_string ( $code, true );
        $code = preg_replace ( '#^<code><span style="color: \#000000">\s*'. 
                               '<span style="color: \#0000BB">&lt;\?php&nbsp;'.
                               '(.+?)</span>(.*?)</span>\s*</code>#s', '\1\2',
                              $code );
        return $code;
    }
}


// Set handlers
set_error_handler ( array ( 'error', 'php_error_handler' ) );
set_exception_handler ( array ( 'error', 'php_exception_handler' ) );
