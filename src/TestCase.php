<?php

namespace cakebake\combiner;

use org\bovigo\vfs\vfsStream;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public $createTestFilesystem = false;

    protected $tmpDir = null;

    private $_filesystem = [];

    protected function setUp()
    {
        $this->tmpDir = realpath(__DIR__ . '/../tmp');
        $this->createTestFilesystem();
    }

    protected function tearDown()
    {
        $this->destroyFilesystem();
    }

    protected function createTestFilesystem()
    {
        if ($this->createTestFilesystem !== true)
            return false;

        //one level, simple php
        $this->createFilesystem('one_level', [
            'index.php' => '<?php
                //start file
                echo "hello world!";
                require dirname(__FILE__) . "/filename1.php";
                require dirname(__FILE__) . "/filename2.php";
                require dirname(__FILE__) . "/empty_file.php";
                require dirname(__FILE__) . "/html.php";
                require dirname(__FILE__) . "/plain.php";
                echo "good bye!";
            ',
            'empty_dir' => [],
            'filename1.php' => '<?php
                echo "Filename1.php";
            ',
            'filename2.php' => '<?php echo "echo in first line...";',
            'empty_file.php' => '       ',
            'html.php' => '<div class="test">Test HTML</div>',
            'plain.php' => 'Plain Text...',
        ]);
    }

    /**
    * Get filesystem by its key
    *
    * @param string $key
    * @return {\org\bovigo\vfs\vfsStreamDirectory|vfsStreamDirectory}
    */
    protected function getFilesystem($key)
    {
        return isset($this->_filesystem[$key]) ? $this->_filesystem[$key] : null;
    }

    /**
    * Create filesystem from array
    *
    * @param string $key
    * @param array $structure
    * @return {\org\bovigo\vfs\vfsStreamDirectory|vfsStreamDirectory}
    */
    protected function createFilesystem($key, array $structure)
    {
        if (!isset($this->_filesystem[$key])) {
            $this->_filesystem[$key] = vfsStream::setup($key);
            $this->_filesystem[$key] = vfsStream::create($structure, $this->_filesystem[$key]);
        }
    }

    /**
    * Destroy filesystem
    *
    * @param string $key 'all' for all stored filesystem or key for specific
    */
    protected function destroyFilesystem($key = 'all')
    {
        if ($key == 'all') {
            $this->_filesystem = null;
        } elseif (isset($this->_filesystem[$key])) {
            unset($this->_filesystem[$key]);
        }
    }

    /**
    * Get an filesystem stream
    *
    * @param string $pointer Filesystem url like 'vfs://app/index.php' where app is the filesystem key
    */
    protected function getFilesystemStream($pointer)
    {
        return vfsStream::url($pointer);
    }

    public function assertFileHasNoErrors($filename)
    {
        $this->assertFileExists($filename);
    }
}