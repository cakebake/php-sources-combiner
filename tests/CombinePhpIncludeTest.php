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
        $this->assertFileExists($this->getFilesystemStream('filename2.php'));
        $this->assertFileExists($this->getFilesystemStream('empty_file.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test1LevelInclude/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelInclude/filename1.php'));
        $this->assertTrue($combine->isParsed('vfs://test1LevelInclude/filename2.php'));
        $this->assertFalse($combine->isParsed('vfs://test1LevelInclude/empty_file.php'), 'Empty files should not be parsed!');
    }

    /**
    * Include level 2
    */
    public function test2LevelsInclude()
    {
        $this->createFilesystem('test2LevelsInclude');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('dir/level-2.php'));
        $this->assertFileExists($this->getFilesystemStream('level-1.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test2LevelsInclude/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsInclude/dir/level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsInclude/html.php'));
        $this->assertTrue($combine->isParsed('vfs://test2LevelsInclude/level-1.php'), 'Level 1 File included from level 2 is missing');
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

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/dir/level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/dir/dir-level-2/level-3.php'), 'Require level 3 from level 2 fail');
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/filename1.php'));
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/level-1.php'), 'Level 1 File included from level 2 is missing');
        $this->assertTrue($combine->isParsed('vfs://test3LevelInclude/dir/level-2-2.php'), 'Level 2 File included from level 3 is missing');
        $this->assertFalse($combine->isParsed('vfs://test3LevelInclude/dsfsdfsdfsdfs.php'), 'File does not exist, file could not parsed');
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

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed('vfs://test10LevelsInclude/index.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsInclude/filename1-level-1.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsInclude/level-1/filename2-level-2.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsInclude/level-1/level-2/level-3/level-4/filename2-level-5.php'));
        $this->assertTrue($combine->isParsed('vfs://test10LevelsInclude/level-1/level-2/level-3/level-4/level-5/level-6/level-7/level-8/level-9/filename1-level-10.php'));
    }
}