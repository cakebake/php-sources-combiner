<?php

use cakebake\combiner\PhpFileCombine;

class CombinePhpCommentsTest extends cakebake\combiner\TestCase
{
    /**
    * Test pretty print of comments beforea and after an include
    */
    public function testTakeCommentsForIncludeStatement()
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
        
        $outCode = $combine->getPrettyCode();
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment start file~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment before require~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment after require~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~\* comment before require~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~\* comment after require~', $outCode), "Text '$search' not found");
        $this->assertGreaterThanOrEqual(1, $c = (int)@preg_match_all($search = '~//comment start in included file~', $outCode), "Text '$search' not found");
    }
}