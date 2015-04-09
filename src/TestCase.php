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

        $filesystem = glob(__DIR__ . '/../tests/filesystem/*.php');
        if (!empty($filesystem)) {
            foreach ($filesystem as $f) {
                if (is_array(($structure = require $f)) && !empty($structure)) {
                    $this->createFilesystem(pathinfo($f, PATHINFO_FILENAME), $structure);
                }
            }
        }
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

    /**
    * Check output file
    *
    * @param string $filename Path to filename
    * @todo More quality checks
    */
    public function assertFileHasNoErrors($filename)
    {
        $this->assertFileExists($filename);
    }
}