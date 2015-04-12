<?php

//two levels, simple php
return [
    'index.php' => '<?php
        //start file
        echo "hello world!";
        echo "level 1";
        include dirname(__FILE__) . "/empty_file.php";
        //include level 2 twice
        include dirname(__FILE__) . "/dir/level-2.php";
        include dirname(__FILE__) . "/dir/level-2.php";
        //end include level 2 twice
        //include level 1
        include dirname(__FILE__) . "/html.php";
        include dirname(__FILE__) . "/plain.php";
        echo "good bye!";
    ',
    'dir' => [
        'level-2.php' => '<?php
            //level 2 file
            echo "i am level 2!";
            //include level 1 from level 2
            include dirname(__FILE__) . "/../level-1.php";

            //include level 3
            include dirname(__FILE__) . "/dir-level-2/level-3.php";

            //continue level 2 file
            echo "END level 2!";
        ',
        'dir-level-2' => [
            'level-3.php' => '<?php
                //level 3 file
                echo "I´m @level-3";
                //include level 1
                include dirname(__FILE__) . "/../../filename1.php"; //comment after require

                //include level 2
                include dirname(__FILE__) . "/../level-2-2.php";

                //end file level 3
            ',
        ],
        'level-2-2.php' => '<?php
            //level 2 file from level 3
            echo "another level-2 file ";
        ',
    ],
    'empty_dir' => [],
    'filename1.php' => '<?php
        echo "Filename1.php"; //on level 1
    ',
    'filename2.php' => '<?php echo "echo in first line...";',
    'empty_file.php' => '       ',
    'html.php' => '<div class="test">Test HTML</div>',
    'plain.php' => 'Plain Text...',
    'level-1.php' => '<?php
        //level 1 file included by level-2.php
        echo "i am level 1!";
    ',
];