<?php

//Namespaces filesystem
return [
    'index.php' => '<?php
        namespace Hello\World;
    
        //comment before require namespaced file
        require dirname(__FILE__) . "/included_file2.php";
        
        require dirname(__FILE__) . "/included_file3.php";
        
        //comment before require
        require dirname(__FILE__) . "/included_file.php";
    ',
    'included_file.php' => '<?php
        //comment start in included file
        echo Test::hello();
    ',
    'included_file2.php' => '<?php
        //comment in required namespaced file
        namespace Hello\World\Check;
        
        class Test
        {
            const TEST = 2;
        }

        //echo Test::TEST;
    ',
    'included_file3.php' => '<?php
        //no namespace
        use \Hello\World\Check\Test as MyTest;
        
        //check conflict
        class Test
        {
            const HELLO = 1;

            public static function hello()
            {
                return self::HELLO . MyTest::TEST;
            }
        }
    ',
];