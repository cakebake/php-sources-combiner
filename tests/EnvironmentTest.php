<?php

class EnvironmentTest extends cakebake\combiner\TestCase
{
    public $createTestFilesystem = true;

    public function testFilesystemApi()
    {
        return $this->assertTrue($this->getFilesystem('one_level')->hasChild('index.php'));
    }

    public function testFilesystemStream()
    {
        $this->assertFileExists($this->getFilesystemStream('one_level/index.php'));
        $this->assertFileExists($this->getFilesystemStream('one_level/filename1.php'));
        $this->assertFileNotExists($this->getFilesystemStream('one_level/test-999.php'));
    }
}