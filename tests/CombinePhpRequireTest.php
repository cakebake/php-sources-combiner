<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpRequireTest extends cakebake\combiner\TestCase
{
    public $createTestFilesystem = true;

    public function testOneLevelIncludes()
    {
        $this->assertFileExists(($stream = $this->getFilesystemStream('one_level/index.php')));
        $this->assertFileExists($this->getFilesystemStream('one_level/filename1.php'));

        $outputFilename = str_replace(array('::', ':', '\\'), '_', __METHOD__) . '.php';

        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('one_level/index.php'),
            'outputDir' => $this->tmpDir,
            'outputFile' => $outputFilename,
        ]);

        $this->assertFileHasNoErrors($this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename);

        $combinedFiles = $combine->getParsedFiles();
        $this->assertTrue(isset($combinedFiles['vfs://one_level/index.php']));
        $this->assertTrue(isset($combinedFiles['vfs://one_level/filename1.php']));
        $this->assertTrue(isset($combinedFiles['vfs://one_level/filename2.php']));
    }
}