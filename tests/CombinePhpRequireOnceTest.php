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

    /**
    * RequireOnce level 10
    */
    public function test10LevelsRequireOnce()
    {
        $structure = require $this->filesystemDir . '/test10LevelsRequireOnce.php';
        array_walk_recursive(
            $structure,
            array('parent', 'interlaceTree'),
            [
                'structure' => $structure,
                'depthLimit' => 10,
            ]
        );

        $this->createFilesystem('test10LevelsRequireOnce', $structure);

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/filename2-level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequireOnce/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequireOnce/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequireOnce/level-1/filename2-level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequireOnce/level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequireOnce/level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));
    }
}