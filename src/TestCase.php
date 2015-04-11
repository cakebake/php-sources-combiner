<?php

namespace cakebake\combiner;

use org\bovigo\vfs\vfsStream;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir = null;

    protected $filesystemDir = null;

    private $_filesystem = null;

    private $_loadedFilesystemKey = null;

    protected function setUp()
    {
        $this->tmpDir = realpath(__DIR__ . '/../tmp');
        $this->filesystemDir = realpath(__DIR__ . '/../tests/filesystem');
    }

    protected function tearDown()
    {
        $this->destroyFilesystem();
    }

    /**
    * Get filesystem by its key
    *
    * @param string $key Obsolet
    * @return {\org\bovigo\vfs\vfsStreamDirectory|vfsStreamDirectory}
    */
    protected function getFilesystem($key = 'obsolet')
    {
        return $this->_filesystem;
    }

    /**
    * Create filesystem from array
    *
    * @param string $key
    * @param array $structure
    * @return {\org\bovigo\vfs\vfsStreamDirectory|vfsStreamDirectory}
    */
    protected function createFilesystem($key, array $structure = array())
    {
        if ($key != $this->_loadedFilesystemKey) {
            if (empty($structure)) {
                if (file_exists(($file = $this->filesystemDir . '/' . $key . '.php'))) {
                    if (is_array(($array = require $file)) && !empty($array)) {
                        $structure = $array;
                    }
                }
            }

            $this->_filesystem = vfsStream::setup($key, null, $structure);
            $this->_loadedFilesystemKey = $key;
        }
    }

    /**
    * Destroy filesystem
    *
    * @param string $key Obsolet
    */
    protected function destroyFilesystem($key = 'obsolet')
    {
        $this->_filesystem = null;
    }

    /**
    * Get an filesystem stream
    *
    * @param string $pointer Filesystem url like 'index.php'
    */
    protected function getFilesystemStream($pointer)
    {
        return vfsStream::url($this->_loadedFilesystemKey . '/' . $pointer);
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

    /**
    * Sanitize filename
    *
    * @param string $filename
    * @param string $seperator
    * @return string
    */
    public static function sanitizeFilename($filename, $seperator = '_')
    {
        $filename = str_ireplace(array('test', '\\', '-', '/', '~', '_', $seperator), '', $filename);
        $filename = str_replace(array('::', ':'), $seperator, $filename);
        $filename = str_replace(array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0)), null, $filename);
        //$filename = str_replace(range('A', 'Z'), explode('~', $seperator . implode("~$seperator", range('a', 'z'))), $filename);
        $filename = trim(trim($filename), $seperator);

        return $filename;
    }

    /**
    * Build recursive tree
    *
    * @param mixed $value
    * @param mixed $key
    * @param mixed $params
    * @param mixed $structure
    * @param mixed $depthLimit
    * @param mixed $subKey
    * @param mixed $level
    */
    public function interlaceTree(&$value, &$key, array $params = [], array $structure = [], $depthLimit = 2, $subKey = 'level', $level = 1)
    {
        extract($params);

        if ($value == 'SUBDIR') {
            $value = [
                'index.php' => '<?php ' . PHP_EOL . '//exit at level-' . ($depthLimit + 1) . PHP_EOL,
            ];
            if ($level < $depthLimit) {
                $oldLevel = $level;
                $level++;

                $value = [];
                foreach ($structure as $k => $v) {
                    $k = str_replace("{$subKey}-{$oldLevel}", "{$subKey}-{$level}", $k);
                    $v = str_replace("{$subKey}-{$oldLevel}", "{$subKey}-{$level}", $v);
                    $value[$k] = $v;
                }

                array_walk_recursive(
                    $value,
                    array(&$this, 'interlaceTree'),
                    [
                        'structure' => $value,
                        'depthLimit' => $depthLimit,
                        'level' => $level,
                    ]
                );
            }
        }
    }
}