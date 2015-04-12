<?php

return [
    'index.php' => '<?php
        //start file level-1
        echo "hello level-1 index!";

        //require_once filename1-level-1.php 3x
        require_once __DIR__ . "/filename1-level-1.php";
        require_once __DIR__ . "/filename1-level-1.php";
        require_once __DIR__ . "/filename1-level-1.php";

        //require_once filename2-level-1.php 1x
        require_once __DIR__ . "/filename2-level-1.php";

        //require_once filename2-level-1.php once again
        require_once __DIR__ . "/filename1-level-1.php";

        //end file level-1
    ',
    'filename1-level-1.php' => '<?php echo "hello filename1-level-1.php";',
    'filename2-level-1.php' => '<?php echo "hello filename2-level-1.php";',
    //dynamic
    'level-1' => 'SUBDIR',
];