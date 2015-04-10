<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpRequireTest extends cakebake\combiner\TestCase
{
    /**
    * Require level 1
    */
    public function test1LevelRequire()
    {
        $this->createFilesystem('test1LevelRequire');

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
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelRequire/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelRequire/filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test1LevelRequire/filename2.php']));
        $this->assertFalse(isset($combinedFiles['vfs://test1LevelRequire/empty_file.php']));
    }

    /**
    * Require level 2
    */
    public function test2LevelsRequire()
    {
        $this->createFilesystem('test2LevelsRequire');

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
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsRequire/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsRequire/dir/level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsRequire/html.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test2LevelsRequire/dir/../level-1.php']), 'Level 1 File included from level 2 is missing');
    }

    /**
    * Require level 3
    */
    public function test3LevelRequire()
    {
        $this->createFilesystem('test3LevelRequire');

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
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/dir/level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/html.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/dir/dir-level-2/level-3.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/dir/dir-level-2/../../filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/dir/../level-1.php']), 'Level 1 File included from level 2 is missing');
        $this->assertTrue(isset($combinedFiles['vfs://test3LevelRequire/dir/dir-level-2/../level-2-2.php']), 'Level 2 File included from level 3 is missing');
    }

}