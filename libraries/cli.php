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

class X_cli
{

    private static 
        $actions = array (
            'controller'    =>  array (
                                        'name',
                                        'Create a controller named \'name\'',
                                    ),
            'model'    =>  array (
                                        'name',
                                        'Create a model named \'name\'',
                                    ),

            'separator 1'   =>  null,

            'test'    =>  array (
                                        '[name or folder]',
                                        'Run all tests (or a specific test if \'name\' is specified)',
                                    ),
            'test_failed'   =>  array (
                                        '[name or folder]',
                                        'Run only failed tests',
                                    ),

            'separator 2'   =>  null,

            'sql'   =>  array (
                                        '*database* sql_file(s)',
                                        'Execute specified file or directory contents against database',
                                    ),

            'separator 3'   =>  null,

            'cc'   =>  array (
                                        null,
                                        'Completely clear out cache',
                                    ),

            'separator 4'   =>  null,

            'run'   =>  array (
                                        'controller [action] [arg1=value1] [arg2=value2] [...]',
                                        'Run a specific controller',
                                    ),

            'separator 5'   =>  null,

            'mongodb_profiler' => array (
                                        '*database* [cache_log_key]',
                                        'Display and flush custom MongoDB profiler data log'
                                    ),

            'separator 6'   =>  null,

            'doctrine_from_yaml'  =>  array (
                                        '*database*',
                                        'Re-create tables in specified database defined in config/global.yml and create doctrine model classes from db/schema/*database*.yml in models/doctrine/*database*'
                                    ),
            'doctrine_from_db'  =>  array (
                                        '*database*',
                                        'Create YAML schema in db/schema/*database*.yml and models in models/doctrine/*database*'
                                    ),
            'doctrine_load_fixtures'  =>  array (
                                        '*database*',
                                        'Attempt to load fixtures from db/fixtures/*database*/ folder'
                                    ),
        ),
        $usage = null;

    /**
     * Run CLI interface
     */
    public static function init ()
    {
        if ( PHP_SAPI != 'cli' ) return;

        self::$usage = "\033[1;37mUsage:\n\t./cli \033[1;36m[action] \033[0;37m[arguments]\033[1;37m\n\n";
        foreach ( self::$actions as $action => $descr )
        {
            if ( $descr == null )
            {
                self::$usage .= "\n";
            }
            else
            {
                self::$usage .= "\t\t\033[1;36m". $action ." \033[0;37m". $descr [ 0 ].
                                "\n\t\t\t\033[0;32m(". $descr [ 1 ] .")\033[1;37m\n";
            }
        }

        $ret = self::parse_args ();

        if ( $ret != 'continue' ) die ( "\033[0m" );
    }


    /**
     * Execute an action based on passed CLI arguments
     */
    private static function parse_args ()
    {
        global $argv;
        
        if ( !isset ( $argv [ 1 ] ) || !isset ( self::$actions [ $argv [ 1 ] ] ) ||
                    self::$actions [ $argv [ 1 ] ] == null )
        {
            echo self::$usage;
            return;
        }
        else
        {
            $args = array_slice ( $argv, 2 );
            return call_user_func_array ( 'X_cli::cli_'. $argv [ 1 ], $args );
        }
    }


    /**
     * Recursive-remove a directory from filesystem
     * @param string $dir 
     */
    private static function rmdir ( $dir )
    {
        foreach ( glob ( $dir .'/*' ) as $f )
        {
            if ( is_dir ( $f ) )
            {
                self::rmdir ( $f );
                rmdir ( $f );
            }
            else
            {
                unlink ( $f );
            }
        }
    }


    /**
     * Create a new controller
     */
    private static function cli_controller ( $name = null )
    {
        if ( $name == null )
        {
            echo self::$usage;
            return;
        }

        if ( !preg_match ( '#^[a-zA-Z0-9_]+$#', $name ) )
        {
            echo "\033[0;31mInvalid name for a controller\n";
            return;
        }

        $path = XT_PROJECT_DIR .'/controllers/'. $name .'_controller.php';

        if ( file_exists ( $path ) )
        {
            echo "\033[0;31m". $name ."_controler already exists\n";
            return;
        }

        $template = "<?php\n\nclass ". $name ."_controller extends controller_base\n".
                    "{\n\n".
                        "    public function __construct ()\n".
                        "    {\n\n".
                        "    }\n\n".
                    "}";
        file_put_contents ( $path,
                            $template );
        echo "\033[0;32mController '". $name ."' created\n";
    }


    /**
     * Create a new model
     */
    private static function cli_model ( $name = null )
    {
        if ( $name == null )
        {
            echo self::$usage;
            return;
        }

        if ( !preg_match ( '#^[a-zA-Z0-9_]+$#', $name ) )
        {
            echo "\033[0;31mInvalid name for a model\n";
            return;
        }

        $path = XT_PROJECT_DIR .'/models/'. $name .'_model.php';

        if ( file_exists ( $path ) )
        {
            echo "\033[0;31m". $name ."_model already exists\n";
            return;
        }

        $template = "<?php\n\nclass ". $name ."_model extends model_base\n".
                    "{\n\n".
                    "}";
        file_put_contents ( $path,
                            $template );
        echo "\033[0;32mmodel '". $name ."' created\n";
    }


    /**
     * Run test(s)
     */
    private static function cli_test ( $test = null, $only_failed = false )
    {
        $dir = XT_PROJECT_DIR .'/tests';
        $file = $dir .'/'. $test .'_test.php';
        if ( $test != null && file_exists ( $file ) && !is_dir ( $file ) )
        {
            // Run a single file
            $scope = function () use ( $file ) { include ( $file ); };
            $scope ();
        }
        else
        {
            // Run multiple files
            $test_files = self::scan_dir (
                    $dir .'/'. $test . ( $test != null ? '/' : null ) .'*',
                    '#_test.php$#' );

            if ( empty ( $test_files ) )
            {
                echo "\033[0;31mCould not find any tests". 
                        ( $test == null ? null :  'matching '. $test ) ."\n";
            }

            X::set ( 'framework', 'unit_test', 'run_all', true );
            $passed_cache = XT_PROJECT_DIR .'/cache/tests_passed';
            $passed_tests = array ();

            if ( $only_failed )
            {
                // Retrieve passed test cache
                if ( !file_exists ( $passed_cache ) )
                {
                    // Null out passed test array
                    file_put_contents ( $passed_cache, null );
                    // Chmod file if it isn't already world-writeable
                    $perms = substr ( decoct ( fileperms ( $passed_cache ) ), -3 );
                    if ( $perms != 777 ) chmod ( $passed_cache, 0777 );
                }
                else
                {
                    $passed_tests = file ( $passed_cache );
                    $passed_tests = array_map ( 'trim', $passed_tests );

                    // Null out passed tests array if all tests completed previously
                    $passed_tests = ( $passed_tests == $test_files ?
                                    array () : array_flip ( $passed_tests ) );
                }
            }

            // Lambda function to save passed test array
            $save_passed = function ( $passed ) use ( $passed_cache )
            {
                global $passed_tests;
                file_put_contents ( $passed_cache,
                                implode ( "\n", array_keys ( $passed ) ) );
            };

            // Variables for calculating passed test percentage
            $executed = 0;
            $passed = 0;

            foreach ( $test_files as $test )
            {
                // Skip if the test passed previously
                if ( $only_failed && isset ( $passed_tests [ $test ] ) )
                {
                    continue;
                }

                $executed++;

                $test_name = substr ( str_replace ( $dir .'/',
                                                    null,
                                                    $test ), 0, -9 );
                X::set ( 'framework', 'unit_test', 'current', $test_name );
                $scope = function () use ( $test ) { include ( $test ); };
                $scope ();

                // Log the test as passed
                if ( X::get ( 'framework', 'unit_test', 'last_passed' ) )
                {
                    $passed_tests [ $test ] = null;
                    $save_passed ( $passed_tests );
                    $passed++;
                }
            }

            // Save the passed test array
            $save_passed ( $passed_tests );

            // Ouput passed test percentage
            if ( $executed == 0 )
            {
                $perc = 100;
            }
            else
            {
                $perc = ($passed *100) /$executed;
            }
            echo "\n". ( $perc == 100 ? "\033[0;42m" : "\033[0;41m" );
            echo "\033[1;37mPassed: $perc% ($passed/$executed)\033[0m\n";
        }
    }


    /**
     * Run only failed tests
     */
    private static function cli_test_failed ( $test = null )
    {
        self::cli_test ( $test, true );
    }


    /**
     * Scan directory recursively
     * @param string $dir
     * @return array
     */
    private static function scan_dir ( $dir, $regex = null )
    {
        $files = array ();
        foreach ( glob ( $dir ) as $f )
        {
            // Recursively read directories
            if ( is_dir ( $f ) )
            {
                $files = array_merge ( $files, self::scan_dir ( $f .'/*', $regex ) );
            }
            else
            {
                if ( ( $regex != null && preg_match ( $regex, $f ) ) || $regex == null )
                {
                    $files [] = $f;
                }
            }
        }
        return $files;
    }


    /**
     * Execute specified SQL file contents against database
     * @param string $database
     * @param string $file
     */
    private static function cli_sql ( $database = null, $file = null )
    {
        if ( $database == null || $file == null )
        {
            echo self::$usage;
            return;
        }

        if ( !X::is_set ( 'config', 'database', $database ) )
        {
            echo "\033[0;31m$database database not defined in config/global.yml\n";
            return;
        }

        // Modify memory_limit to allow large dumps to be executed
        ini_set ( 'memory_limit', '4G' );

        $pdo = new xt_pdo ( $database );

        $sql_files = array ();

        $files = func_get_args ();
        array_shift ( $files );
        foreach ( $files as $fname )
        {
            $fname = realpath ( $fname );
            if ( $fname == null ) continue;

            if ( is_dir ( $fname ) )
            {
                $sql_files = array_merge ( $sql_files, self::scan_dir ( $fname, '#.sql$#' ) );
            }
            else
            {
                $sql_files [] = $fname;
            }
        }

        if ( empty ( $sql_files ) )
        {
            echo "\033[0;31mNo matching .sql files found\n";
            return;
        }

        foreach ( $sql_files as $fname )
        {
            $res = $pdo -> execute_dump ( file_get_contents ( $fname ) );
            if ( $res )
            {
                echo "\033[0;32mExecuted $fname\n";
            }
            else
            {
                echo "\033[0;31mFailed to execute $fname\n";
            }
        }
    }


    /**
     * Clear the entire cache
     */
    private static function cli_cc ()
    {
        $files = self::scan_dir ( XT_PROJECT_DIR .'/cache' );
        $files_cache_dir = XT_PROJECT_DIR .'/'.
                                X::get ( 'config', 'cache', 'files', 'dir' );
        $len = strlen ( $files_cache_dir );
        foreach ( $files as $f )
        {
            // Check if the dir doesn't match files cache dir
            if ( substr ( $f, 0, $len ) != $files_cache_dir )
            {
                unlink ( $f );
            }
        }

        X_cache::clear ();
        echo "\033[0;32mCache cleared (cache/*, ". X::get ( 'framework', 'cache_backend' ) .")\n";
    }


    /**
     * Run a specific controller
     */
    private static function cli_run ( $controller = null, $action = null )
    {
        if ( $controller == null )
        {
            echo self::$usage;
            return;
        }

        $routing = array ( 'controller' => $controller );
        if ( $action != null ) $routing [ 'action' ] = $action;

        // Parse optional variables
        $args = array_slice ( func_get_args (), 2 );
        foreach ( $args as $arg )
        {
            $pair = explode ( '=', $arg, 2 );
            if ( !isset ( $pair [ 1 ] ) ) continue;
            $routing [ $pair [ 0 ] ] = $pair [ 1 ];
        }

        X::set ( 'route', $routing );


        // Continue execution of the framework (beyond CLI lib)
        return 'continue';
    }


    /**
     * Display and flush mongo profiler data log
     */
    private static function cli_mongodb_profiler ( $database = null, $cache_key = null )
    {
        if ( $database == null )
        {
            echo self::$usage;
            return;
        }

        if ( X::is_set ( 'config', 'database', $database, 'profiler', 'cache_log_key' ) )
        {
            $key = X::get ( 'config', 'database', $database, 'profiler', 'cache_log_key' );
        }
        elseif ( $cache_key != null )
        {
            $key = $cache_key;
        }
        else
        {
            echo "\033[0;33m$database database does not have profiler configuration in config/global.yml in this environment (cli). Defaulting to 'X_xt_mongo_profiler_'. Otherwise you can explicitly specify cache_log_key via cli.\n";
            $key = 'X_xt_mongo_profiler_';
        }

        $key .= $database;

        echo "\033[0;32mProfiler running, real-time data:\033[0m\n";
        set_time_limit ( 0 );
        ob_end_flush (); // Otherwise output is saved in output buffer

        while ( 1 )
        {
            $last_url = null;
            $log = X_cache::get ( $key );
            if ( $log != false )
            {
                $log = unserialize ( $log );
                $log = array_reverse ( $log );
                foreach ( $log as $query )
                {
                    //$url, $db, $collection, $backtrace, $function, $arguments, $time, $explain

                    if ( $last_url != $query [ 0 ] )
                    {
                        echo "\r". $query [ 0 ] ."\n";
                    }
                    $last_url = $query [ 0 ];

                    foreach ( $query [ 5 ] as &$arg )
                    {
                        if ( is_array ( $arg ) )
                        {
                            $arg = str_replace ( "\n", null, print_r ( $arg, 1 ) );
                            $arg = preg_replace ( '#\s{2,}#', ' ', $arg );
                        }
                        if ( strlen ( $arg ) > 30 ) $arg = substr ( $arg, 0, 30 ) .'...';
                    }

                    $arguments = implode ( ', ', $query [ 5 ] );
                    echo "\t". $query [ 1 ] .' -> '. $query [ 2 ] .' -> '.
                            $query [ 4 ] ." ( ". $arguments ." ):\n";
                    echo "\t    ". $query [ 3 ] [ 'file' ] .':'. $query [ 3 ] [ 'line' ] ."\n";
                    echo "\t    ". round ( $query [ 6 ], 4 ) ." sec\n";

                    if ( isset ( $query [ 7 ] ) && $query [ 7 ] != null )
                    {
                        $explain = str_replace ( "\n", null, print_r ( $query [ 7 ], 1 ) );
                        $explain = preg_replace ( '#\s{2,}#', ' ', $explain );
                        echo "\t    ". $explain ."\n";
                    }
                }
                echo "\033[0m\n";
                X_cache::delete ( $key );
            }

            echo "\r\033[1;31m^C to stop\033[0m";
            sleep ( 1 );
        }
    }


    /**
     * Doctrine CLI interface
     */
    private static function cli_doctrine_from_yaml ( $database = null )
    {
        if ( $database == null )
        {
            echo self::$usage;
            return;
        }

        if ( !X::is_set ( 'config', 'database', $database ) )
        {
            echo "\033[0;31m$database database not defined in config/global.yml\n";
            return;
        }

        $schema = XT_PROJECT_DIR .'/db/schema/'. $database .'.yml';
        if ( !file_exists ( $schema ) )
        {
            echo "\033[0;31mdb/schema/$database.yml does not exist\n";
            return;
        }

        $models_dir = XT_PROJECT_DIR .'/models/doctrine/'. $database;
        if ( is_dir ( $models_dir ) )
        {
            self::rmdir ( $models_dir );
        }
        else
        {
            mkdir ( $models_dir, 0777, true );
        }

        new xt_doctrine_connection ( $database, false );

        $options = array (
                'baseClassesDirectory'  =>  'base',
                'classPrefix'   =>  $database .'_',
                'generateTableClasses'  =>  true,
            );

        Doctrine_Core::dropDatabases();
        Doctrine_Core::createDatabases();
        Doctrine_Core::generateModelsFromYaml ( $schema, $models_dir, $options );
        Doctrine_Core::createTablesFromModels ( $models_dir );

        echo "\033[0;32mDatabase and models created\n";
    }


    /**
     * Doctrine CLI interface
     */
    private static function cli_doctrine_from_db ( $database = null )
    {
        if ( $database == null )
        {
            echo self::$usage;
            return;
        }

        if ( !X::is_set ( 'config', 'database', $database ) )
        {
            echo "\033[0;31m$database database not defined in config/global.yml\n";
            return;
        }

        $schema = XT_PROJECT_DIR .'/db/schema/'. $database .'.yml';
        if ( file_exists ( $schema ) )
        {
            unlink ( $schema );
        }

        $models_dir = XT_PROJECT_DIR .'/models/doctrine/'. $database;
        if ( is_dir ( $models_dir ) )
        {
            self::rmdir ( $models_dir );
        }
        else
        {
            mkdir ( $models_dir, 0777, true );
        }

        new xt_doctrine_connection ( $database, false );

        $options = array (
                'baseClassesDirectory'  =>  'base',
                'classPrefix'   =>  $database .'_',
                'generateTableClasses'  =>  true,
            );

        Doctrine_Core::generateModelsFromDb ( $models_dir, array ( $database ), $options );
        Doctrine_Core::generateYamlFromModels ( $schema, $models_dir );

        echo "\033[0;32mYAML schema and models created\n";
    }


    /**
     * Doctrine CLI interface
     */
    private static function cli_doctrine_load_fixtures ( $database = null )
    {
        if ( $database == null )
        {
            echo self::$usage;
            return;
        }

        if ( !X::is_set ( 'config', 'database', $database ) )
        {
            echo "\033[0;31m$database database not defined in config/global.yml\n";
            return;
        }

        $fixtures_dir = XT_PROJECT_DIR .'/db/fixtures/'. $database;
        if ( !is_dir ( $fixtures_dir ) )
        {
            echo "\033[0;31mFixtures directory $fixtures_dir does not exist\n";
            return;
        }

        new xt_doctrine_connection ( $database );

        Doctrine_Core::loadData ( $fixtures_dir );

        echo "\033[0;32mFixtures loaded\n";
    }

}