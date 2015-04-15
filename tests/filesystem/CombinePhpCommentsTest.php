<?php

//CombinePhpCommentsTest filesystem
return [
    'index.php' => '<?php
        //comment start file
        echo "hello world!";

        //comment before require
        require dirname(__FILE__) . "/included_file.php";
        //comment after require
        
        /**
        * comment before require
        */
        require dirname(__FILE__) . "/included_file.php";
        /**
        * comment after require
        */
        
        include_once(dirname(__FILE__) . "/test.php");
        
        //comment echo
        echo "good bye!";
        //comment end file
    ',
    'included_file.php' => '<?php
        //comment start in included file
        echo "I am included_file.php file...";
        //comment end in included file
    ',
    'test.php' => '<?php
        //no comments before and after require this file
        echo "test.php";
    ',
];