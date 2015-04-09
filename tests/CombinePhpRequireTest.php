<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpRequireTest extends cakebake\combiner\TestCase
{
    /**
    * Require level 1
    */
    public function testOneLevelRequire()
    {
        $this->createFilesystem('testOneLevelRequire');

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
        $this->assertTrue(isset($combinedFiles['vfs://testOneLevelRequire/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://testOneLevelRequire/filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://testOneLevelRequire/filename2.php']));
        $this->assertFalse(isset($combinedFiles['vfs://testOneLevelRequire/empty_file.php']));
    }

    /**
    * Require level 2
    */
    public function testTwoLevelsRequire()
    {
        $this->createFilesystem('testTwoLevelRequire');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2.php'));

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
        //DebugBreak();
        $this->assertTrue(isset($combinedFiles['vfs://testTwoLevelRequire/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://testTwoLevelRequire/dir/level-2.php']));
        $this->assertTrue(isset($combinedFiles['vfs://testTwoLevelRequire/html.php']));
        $this->assertTrue(isset($combinedFiles['vfs://testTwoLevelRequire/dir/../level-1.php']), 'Level 1 File included from level 2 is missing');
    }
}