<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpIncludeTest extends cakebake\combiner\TestCase
{
    /**
    * Include level 1
    */
    public function test1LevelInclude()
    {
        $this->createFilesystem('test1LevelInclude');

        $this->assertFileExists($this->getFilesystemStream('index.php'));

        $outputFilename = self::sanitizeFilename(__METHOD__ . '.php');

        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('index.php'),
            'outputDir' => $this->tmpDir,
            'outputFile' => $outputFilename,
            'removeDebugInfo' => true,
            'removeComments' => true,
        ]);

        $this->assertFileHasNoErrors($this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename);

        $combinedFiles = $combine->getParsedFiles();
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelInclude/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelInclude/filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelInclude/filename2.php']));
        $this->assertFalse(isset($combinedFiles['vfs://test1LevelInclude/empty_file.php']));
    }

    /**
    * Include level 2
    */
    public function test2LevelsInclude()
    {
        $this->createFilesystem('test2LevelsInclude');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/../level-1.php'));

        $outputFilename = self::sanitizeFilename(__METHOD__ . '.php');

        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('index.php'),
            'outputDir' => $this->tmpDir,
            'outputFile' => $outputFilename,
            'removeDebugInfo' => false,
            'removeComments' => false,
        ]);

        $this->assertFileHasNoErrors($this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename);

        $combinedFiles = $combine->getParsedFiles();
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsInclude/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsInclude/dir/level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsInclude/html.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsInclude/dir/../level-1.php']), 'Level 1 File included from level 2 is missing');
    }

    /**
    * Include level 3
    */
    public function test3LevelInclude()
    {
        $this->createFilesystem('test3LevelInclude');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/dir-level-2/level-3.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2-2.php'));

        $outputFilename = self::sanitizeFilename(__METHOD__ . '.php');

        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('index.php'),
            'outputDir' => $this->tmpDir,
            'outputFile' => $outputFilename,
            'removeDebugInfo' => true,
            'removeComments' => false,
        ]);

        $this->assertFileHasNoErrors($this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename);

        $combinedFiles = $combine->getParsedFiles();
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/dir/level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/html.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/dir/dir-level-2/level-3.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/dir/dir-level-2/../../filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/dir/../level-1.php']), 'Level 1 File included from level 2 is missing');
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelInclude/dir/dir-level-2/../level-2-2.php']), 'Level 2 File included from level 3 is missing');
    }


    /**
    * Include level 10
    */
    public function test10LevelsInclude()
    {
        $structure = require $this->filesystemDir . '/test10LevelsInclude.php';
        array_walk_recursive(
            $structure,
            array('parent', 'interlaceTree'),
            [
                'structure' => $structure,
                'depthLimit' => 10,
            ]
        );

        $this->createFilesystem('test10LevelsInclude', $structure);

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/filename2-level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));

        $outputFilename = self::sanitizeFilename(__METHOD__ . '.php');
        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('index.php'),
            'outputDir' => $this->tmpDir,
            'outputFile' => $outputFilename,
            'removeDebugInfo' => false,
            'removeComments' => true,
        ]);

        $this->assertFileHasNoErrors($this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename);

        $combinedFiles = $combine->getParsedFiles();
        $this->assertTrue(isset($combinedFiles['vfs://test10LevelsInclude/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test10LevelsInclude/filename1-level-1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test10LevelsInclude/level-1/filename2-level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test10LevelsInclude/level-1/level-2/level-3/level-4/filename2-level-5.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test10LevelsInclude/level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php']));
    }
}