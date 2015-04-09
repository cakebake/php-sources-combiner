<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpRequireTest extends cakebake\combiner\TestCase
{
    public $createTestFilesystem = true;

    public function testOneLevelRequire()
    {
        $this->assertFileExists(($stream = $this->getFilesystemStream('testOneLevelRequire/index.php')));

        $outputFilename = str_replace(array('::', ':', '\\'), '_', __METHOD__) . '.php';

        $combine = new PhpFileCombine([
            'startFile' => $this->getFilesystemStream('testOneLevelRequire/index.php'),
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
}