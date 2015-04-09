<?php

class EnvironmentTest extends cakebake\combiner\TestCase
{
    public $createTestFilesystem = true;

    public function testFilesystemApi()
    {
        return $this->assertTrue($this->getFilesystem('testOneLevelRequire')->hasChild('index.php'));
    }

    public function testFilesystemStream()
    {
        $this->assertFileExists($this->getFilesystemStream('testOneLevelRequire/index.php'));
        $this->assertFileExists($this->getFilesystemStream('testOneLevelRequire/filename1.php'));
        $this->assertFileNotExists($this->getFilesystemStream('testOneLevelRequire/test-999.php'));
    }
}