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

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequire/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequire/filename1.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelRequire/filename2.php'));
        $this->assertFalse($combine->isParsed('vfs://test1LevelRequire/empty_file.php'), 'Empty files should not be parsed!');
    }

    /**
    * Require level 2
    */
    public function test2LevelsRequire()
    {
        $this->createFilesystem('test2LevelsRequire');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test2LevelsRequire/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsRequire/dir/level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsRequire/html.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsRequire/level-1.php'), 'Level 1 File included from level 2 is missing');
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

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/dir/level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/dir/dir-level-2/level-3.php'), 'Require level 3 from level 2 fail');
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/filename1.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/level-1.php'), 'Level 1 File included from level 2 is missing');
        $this->assertTrue($combine->isParsed('vfs://test3LevelRequire/dir/level-2-2.php'), 'Level 2 File included from level 3 is missing');
        $this->assertFalse($combine->isParsed('vfs://test3LevelRequire/dsfsdfsdfsdfs.php'), 'File does not exist, file could not parsed');
    }


    /**
    * Require level 10
    */
    public function test10LevelsRequire()
    {
        $structure = require $this->filesystemDir . '/test10LevelsRequire.php';
        array_walk_recursive(
            $structure,
            array('parent', 'interlaceTree'),
            [
                'structure' => $structure,
                'depthLimit' => 10,
            ]
        );

        $this->createFilesystem('test10LevelsRequire', $structure);

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1-level-1.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/filename2-level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequire/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequire/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequire/level-1/filename2-level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequire/level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsRequire/level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));
    }
}