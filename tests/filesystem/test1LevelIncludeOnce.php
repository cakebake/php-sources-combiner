<?php

return [
    'index.php' => '<?php
        //start file level-1
        echo "hello level-1 index!";

        //include_once filename1-level-1.php 3x
        include_once __DIR__ . "/filename1-level-1.php";
        include_once dirname(__FILE__) . "/filename1-level-1.php";
        include_once __DIR__ . "/filename1-level-1.php";

        //include_once filename2-level-1.php 1x
        include_once __DIR__ . "/filename2-level-1.php";

        //include_once filename2-level-1.php once again
        include_once __DIR__ . "/filename1-level-1.php";

        //end file level-1
    ',
    'filename1-level-1.php' => '<?php echo "hello filename1-level-1.php";',
    'filename2-level-1.php' => '<?php echo "hello filename2-level-1.php";',
];