<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpConvertNamespacesTest extends cakebake\combiner\TestCase
{
    public function testConvertNamespaces()
    {
        $filesystemName = __CLASS__;
        $this->createFilesystem($filesystemName);

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('included_file.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed("vfs://$filesystemName/index.php"));
        $this->assertTrue($combine->isParsed("vfs://$filesystemName/included_file.php"));
    }
}