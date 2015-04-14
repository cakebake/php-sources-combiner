<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpIncludeOnceTest extends cakebake\combiner\TestCase
{
    /**
    * IncludeOnce level 1
    */
    public function test1LevelIncludeOnce()
    {
        $this->createFilesystem('test1LevelIncludeOnce');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('filename2-level-1.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $this->assertInstanceOf('cakebake\combiner\PhpFileCombine', ($combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath)));

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test1LevelIncludeOnce/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelIncludeOnce/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelIncludeOnce/filename2-level-1.php'));

        $this->assertEquals(preg_match_all('~hello filename1-level-1.php~', $combine->getPrettyCode()), 1, 'String "hello filename1-level-1.php" may occure only once');
    }

    /**
    * IncludeOnce level 10
    */
    public function test10LevelsIncludeOnce()
    {
        $structure = require $this->filesystemDir . '/test10LevelsIncludeOnce.php';
        array_walk_recursive(
            $structure,
            array('parent', 'interlaceTree'),
            [
                'structure' => $structure,
                'depthLimit' => 10,
            ]
        );

        $this->createFilesystem('test10LevelsIncludeOnce', $structure);

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/filename2-level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $this->assertInstanceOf('cakebake\combiner\PhpFileCombine', ($combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath)));

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test10LevelsIncludeOnce/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsIncludeOnce/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsIncludeOnce/level-1/filename2-level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsIncludeOnce/level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsIncludeOnce/level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));

        $this->assertEquals(preg_match_all('~hello filename1-level-1.php~', $combine->getPrettyCode()), 1, 'String "hello filename1-level-1.php" may occure only once');
    }
}