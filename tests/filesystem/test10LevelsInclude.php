<?php

//one level, simple php
return [
    'index.php' => '<?php
        //start file level-1
        echo "hello level-1 index!";

        //include level-1
        include __DIR__ . "/filename1-level-1.php";
        include __DIR__ . "/filename2-level-1.php";

        //include next level from level-1
        include __DIR__ . "/level-1/index.php";

        //end file level-1
    ',
    'filename1-level-1.php' => '<?php echo "hello filename1-level-1.php";',
    'filename2-level-1.php' => '<?php echo "hello filename2-level-1.php";',
    //dynamic
    'level-1' => 'SUBDIR',
];