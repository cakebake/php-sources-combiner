<?php

//one level, simple php
return [
    'index.php' => '
<p>Hello world, we start with html...</p>
<?= 12345 ?>
<br />

<?php
        //start file
        echo "hello world!";

        //include `filename1.php` 5x
        include dirname(__FILE__) . "/filename1.php";
        include dirname(__FILE__) . "/filename1.php";
        include dirname(__FILE__) . "/filename1.php";
        include dirname(__FILE__) . "/filename1.php";
        include dirname(__FILE__) . "/filename1.php";
        //end `filename1.php` 5x

        include dirname(__FILE__) . "/filename2.php";
        include dirname(__FILE__) . "/empty_file.php";
        include dirname(__FILE__) . "/html.php";
        include dirname(__FILE__) . "/plain.php";
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