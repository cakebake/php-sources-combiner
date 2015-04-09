<?php

//one level, simple php
return [
    'index.php' => '<?php
        //start file
        echo "hello world!";
        require dirname(__FILE__) . "/filename1.php";
        require dirname(__FILE__) . "/filename2.php";
        require dirname(__FILE__) . "/empty_file.php";
        require dirname(__FILE__) . "/html.php";
        require dirname(__FILE__) . "/plain.php";
        echo "good bye!";
    ',
    'empty_dir' => [],
    'filename1.php' => '<?php
        echo "Filename1.php";
    ',
    'filename2.php' => '<?php echo "echo in first line...";',
    'empty_file.php' => '       ',
    'html.php' => '<div class="test">Test HTML</div>',
    'plain.php' => 'Plain Text...',
];