<?php

//two levels, simple php
return [
    'index.php' => '<?php
        //start file
        echo "hello world!";
        //require level 1
        require dirname(__FILE__) . "/filename1.php";
        require dirname(__FILE__) . "/filename2.php";
        echo "level 1";
        require dirname(__FILE__) . "/empty_file.php";
        //require level 2 twice
        require dirname(__FILE__) . "/dir/level-2.php";
        require dirname(__FILE__) . "/dir/level-2.php";
        //end require level 2 twice
        //require level 1
        require dirname(__FILE__) . "/html.php";
        require dirname(__FILE__) . "/plain.php";
        echo "good bye!";
    ',
    'dir' => [
        'level-2.php' => '<?php
            //level 2 file
            echo "i am level 2!";
            //require level 1 from level 2
            require dirname(__FILE__) . "/../level-1.php";
            //continue level 2 file
            echo "END level 2!";
        ',
    ],
    'empty_dir' => [],
    'filename1.php' => '<?php
        echo "Filename1.php";
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