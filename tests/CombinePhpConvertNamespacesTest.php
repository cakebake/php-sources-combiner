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
        $this->assertFileExists($this->getFilesystemStream('included_file2.php'));

        $startFile = $this->getFilesystemStream('index.php');
        $outPath = $this->tmpDir . '/' . self::sanitizeFilename(__METHOD__ . '.php');
        $combine = PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);

        $this->assertFileHasNoErrors($outPath);
        $this->assertTrue($combine->isParsed("vfs://$filesystemName/index.php"));
        $this->assertTrue($combine->isParsed("vfs://$filesystemName/included_file.php"));
        $this->assertTrue($combine->isParsed("vfs://$filesystemName/included_file2.php"));
        
        $outCode = $combine->getPrettyCode();
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment before require~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment before require namespaced file~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment in required namespaced file~', $outCode), "Text '$search' not found");
    }
}