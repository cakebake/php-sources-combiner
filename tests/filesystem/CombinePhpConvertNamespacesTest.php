<?php

//CombinePhpCommentsTest filesystem
return [
    'index.php' => '<?php
        namespace Hello\World;

        class Test
        {
            const HELLO = 1;

            public static function hello()
            {
                return "hello";
            }
        }

        namespace Hello\Foo;

        use \Hello\World\Test as Alias;

        echo Alias::hello();
        echo Alias::HELLO;

        require dirname(__FILE__) . "/included_file.php";
    ',
    'included_file.php' => '<?php
        //comment start in included file
        echo \Hello\World\Test::hello();
        echo \Hello\World\Test::HELLO;

        class demo
        {
            const TEST = 1;
        }

        echo demo::TEST;
    ',
];