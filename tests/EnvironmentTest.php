<?php

class EnvironmentTest extends cakebake\combiner\TestCase
{
    public function testFilesystemApi()
    {
        $this->createFilesystem('testOneLevelRequire');

        return $this->assertTrue($this->getFilesystem()->hasChild('index.php'));
    }

    public function testFilesystemStream()
    {
        $this->createFilesystem('testOneLevelRequire');

        $this->assertFileExists($this->getFilesystemStream('index.php'));
        $this->assertFileExists($this->getFilesystemStream('filename1.php'));
        $this->assertFileNotExists($this->getFilesystemStream('test-999.php'));
    }
}