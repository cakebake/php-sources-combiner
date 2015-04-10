<?php

class EnvironmentTest extends cakebake\combiner\TestCase
{
    public function testFilesystemApi()
    {
        $this->createFilesystem('test1LevelRequire');

        return $this->assertTrue($this->getFilesystem()->hasChild('index.php'));
    }

    public function testFilesystemStream()
    {
        $this->createFilesystem('test1LevelRequire');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1.php'));
        $this->assertFileNotExists($this->getFilesystemStream('test-999.php'));
    }
}