<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpRequireOnceTest extends cakebake\combiner\TestCase
{
    /**
    * RequireOnce level 1
    */
    public function test1LevelRequireOnce()
    {
        $this->createFilesystem('test1LevelRequireOnce');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('filename2-level-1.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequireOnce/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequireOnce/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequireOnce/filename2-level-1.php'));
    }
}