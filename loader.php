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

/**
 * @todo mongo profiler backtraces
 * @todo mongo profiler prettify cli output
 * 
 * @todo fully implement X_cache::keys
 * @todo dev environment web toolbar
 * @todo Auth
 */

//if ( !defined ( 'XT_PROJECT_DIR' ) ) die ( 'Define XT_PROJECT_DIR constant in your bootstrap file' );

// Global constant for tracking framework's directory
define ( 'XT_FRAMEWORK_DIR', dirname ( __FILE__ ) );

#compiler include: bin/create_project.php
#compiler skip start
// Load project creation CLI interface
include ( XT_FRAMEWORK_DIR .'/bin/create_project.php' );
#compiler skip end

#compiler skip start
// Load error handler
include ( XT_FRAMEWORK_DIR .'/error_handler.php' );
// Adjust some PHP settings
include ( XT_FRAMEWORK_DIR .'/environment.php' );
#compiler skip end
#compiler include: error_handler.php
#compiler include: environment.php

try
{
    #compiler skip start
    include ( XT_FRAMEWORK_DIR .'/libraries/framework.php' );
    #compiler skip end
    #compiler include: libraries/framework.php

    // Wroom wroom
    X::init ();

    // Prepare the view environment
    X_view::init ();

    if ( X_view::get () !== false )
    {
        // Update the cached view if needed
        X_view::compile ( X_view::template () .'/'. X_view::get () );

        // Run it!
        include ( XT_VIEW_CACHE_DIR .'/'. X_view::template () .'/'. X_view::get () );
    }

    ob_end_flush ();
}
catch ( error $exception )
{
    error::show ( $exception );
}
catch ( MongoException $exception )
{
    error::show ( $exception );
}
catch ( MongoCursorException $exception )
{
    error::show ( $exception );
}
catch ( MongoCursorTimeoutException $exception )
{
    error::show ( $exception );
}
catch ( MongoConnectionException $exception )
{
    error::show ( $exception );
}
catch ( MongoGridFSException $exception )
{
    error::show ( $exception );
}