<?php

namespace cakebake\combiner;

use cakebake\combiner\NodeVisitor\IncludeNodeVisitor;
use cakebake\combiner\NodeVisitor\NamespaceNodeVisitor;
use PhpParser\Lexer\Emulative as LexerEmulative;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;


/**
 * PhpFileCombine
 *
 * @example PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);
 */
class PhpFileCombine
{
    private $_parentFile = null;
    private $_currentFile = null;
    private $_outFile = null;
    private $_parsedFiles = [];
    private $_fileKeys = [];
    private $_parser = null;
    private $_prettyPrinter = null;
    private $_cacheDir = null;

    /**
     * Static class constructor
     *
     * @return PhpFileCombine
     */
    public static function init()
    {
        return self::createInstance();
    }

    /**
     * Write the output file
     *
     * @param string $filename
     * @param string $prettyCode
     * @return PhpFileCombine|false Object or false on write failure
     */
    public function writeFile($filename, $prettyCode = null)
    {
        $this->setOutputFile($filename);

        if ($prettyCode !== null) {
            $this->setPrettyCode($prettyCode);
        }

        return (@file_put_contents($this->getOutputFile(), $this->getPrettyCode(), LOCK_EX) !== false) ? $this : false;
    }

    /**
     * Pretty prints the stmts tree
     *
     * @param bool  $finalPrint Adds php tags
     * @param array $stmts      Stmts tree
     * @return PhpFileCombine
     */
    public function prettyPrint($finalPrint = false, array $stmts = [])
    {
        if (!empty($stmts)) {
            $this->setStmts($stmts);
        }

        if ($finalPrint === true) {
            $this->setPrettyCode($this->getPrettyPrinter()->prettyPrintFile($this->getStmts()));
        } else {
            $this->setPrettyCode($this->getPrettyPrinter()->prettyPrint($this->getStmts()));
        }

        return $this;
    }

    /**
     * Stmts tree setter from file
     *
     * @param string $currentFile
     * @param string $parentFile
     * @return PhpFileCombine
     */
    public function parseFile($currentFile, $parentFile = null)
    {
        if (($orgCode = $this->getFileContent($currentFile)) === null)
            return false;

        $this->setParentFile($parentFile, $currentFile);
        $this->setCurrentFile($currentFile);
        $this->parse($orgCode);

        return $this;
    }

    /**
     * Stmts tree setter from code
     *
     * @param string $code
     * @return PhpFileCombine
     */
    public function parse($code)
    {
        $this->setOrgCode($code);
        $this->setStmts($this->getParser()->parse($code));
        $this->setFileCacheStmts();
        $this->setNamespace();

        return $this;
    }

    /**
     * Set namespace, when no namespace exists
     *
     * @param array $stmts
     * @return PhpFileCombine
     */
    public function setNamespace(array $stmts = [])
    {
        $stmts = empty($stmts) ? $this->getStmts() : $stmts;
        if (isset($stmts[0]) && ($stmts[0] instanceof Namespace_ === false)) {
            $this->setStmts([new Namespace_(new Name('JA' . $this->getFileKey()), $stmts)]);
        }

        return $this;
    }

    /**
     * Traverse stmts tree
     *
     * @param array $stmts
     * @return PhpFileCombine
     */
    public function traverse(array $stmts = [])
    {
        if (!empty($stmts)) {
            $this->setStmts($stmts);
        }

        $this->traverseIncludeNodes();
        $this->traverseNamespaceNodes();

        return $this;
    }

    /**
     * Get node traverser and its include visitor
     *
     * @return \PhpParser\NodeTraverser
     * @internal param array $stmts
     */
    public function traverseIncludeNodes()
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new IncludeNodeVisitor($this, $this->getCurrentFile()));

        $this->setStmts($traverser->traverse($this->getStmts()));

        return $this;
    }

    /**
     * Get node traverser and its namespace visitor
     *
     * @return \PhpParser\NodeTraverser
     * @internal param array $stmts
     */
    public function traverseNamespaceNodes()
    {
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new NamespaceNodeVisitor);

        $this->setStmts($traverser->traverse($this->getStmts()));

        return $this;
    }

    /**
     * @param $filePath
     * @return null|string
     */
    public function getFileContent($filePath)
    {
        return (($c = @trim(@file_get_contents($filePath))) === false || empty($c)) ? null : $c;
    }

    /**
     * Get cache path
     *
     * @return string|null
     */
    public function getCacheDir()
    {
        if ($this->_cacheDir === null) {
            $this->setCacheDir(__DIR__ . '/../tmp/cache');
        }

        return $this->_cacheDir;
    }

    /**
     * Overwrites default cache path
     *
     * @param string $cacheDir
     * @return null|string
     */
    public function setCacheDir($cacheDir)
    {
        return $this->_cacheDir = (is_dir($cacheDir) && is_writable($cacheDir)) ? $cacheDir : null;
    }

    /**
     * Checks if cache file exists and returns path to it
     *
     * @param $filePath
     * @return null|string
     */
    public function getFileCachePath($filePath)
    {
        if (($cacheDir = $this->getCacheDir()) === null)
            return null;

        return file_exists(($cachePath = $cacheDir . '/' . $this->getFileKey($filePath))) ? $cachePath : null;
    }

    /**
     * Get cached stmts tree of given file path
     *
     * @param $filePath
     * @return null|string
     */
    public function getFileCacheStmts($filePath)
    {
        if (($cachePath = $this->getFileCachePath($filePath)) === null)
            return null;

        if (($cache = $this->getFileContent($cachePath)) === null)
            return null;

        return unserialize($cache);
    }

    /**
     * Set stmts cache for current file
     *
     * @param null $stmts
     * @return bool|\cakebake\combiner\PhpFileCombine
     */
    public function setFileCacheStmts()
    {
        if (($cacheDir = $this->getCacheDir()) === null)
            return false;

        return (@file_put_contents($cacheDir . '/' . $this->getFileKey(), serialize($this->getStmts()), LOCK_EX) !== false) ? $this : false;
    }

    /**
     * Get current file
     *
     * @return string
     */
    public function getCurrentFile()
    {
        return $this->_currentFile;
    }

    /**
     * Set current file path
     *
     * @param string $value
     * @param string $storage
     * @return string
     */
    public function setCurrentFile($value, $storage = null)
    {
        $this->_currentFile = $value;

        return $this->setParserData('orgFile', $value, $storage);
    }

    /**
     * Get parent file path
     *
     * @param string $storage
     * @return string
     */
    public function getParentFile($storage = null)
    {
        if ($storage !== null) {
            return $this->getParserData($storage)['parentFile'];
        }

        return $this->_parentFile;
    }

    /**
     * Set parent file path
     *
     * @param string $value
     * @param string $storage
     * @return string
     */
    public function setParentFile($value, $storage = null)
    {
        $this->_parentFile = $value;

        return $this->setParserData('parentFile', $value, $storage);
    }

    /**
     * Get output file
     *
     * @return string
     */
    public function getOutputFile()
    {
        return $this->_outFile;
    }

    /**
     * Set global output file
     *
     * @param string $value
     * @return string
     */
    public function setOutputFile($value)
    {
        return $this->_outFile = $value;
    }

    /**
     * Get stmts tree
     *
     * @param string $storage
     * @return array Stmts tree
     */
    public function getStmts($storage = null)
    {
        return $this->getParserData($storage)['stmts'];
    }

    /**
     * Set stmts tree to storage
     *
     * @param array  $value
     * @param string $storage
     * @return array
     */
    public function setStmts(array $value, $storage = null)
    {
        return $this->setParserData('stmts', $value, $storage);
    }

    /**
     * Get original code from storage
     *
     * @param string $storage
     * @return string
     */
    public function getOrgCode($storage = null)
    {
        return $this->getParserData($storage)['orgCode'];
    }

    /**
     * Set original code to storage
     *
     * @param string $value
     * @param string $storage
     * @return string
     */
    public function setOrgCode($value, $storage = null)
    {
        return $this->setParserData('orgCode', $value, $storage);
    }

    /**
     * Get pretty code
     *
     * @param string $storage
     * @return string
     */
    public function getPrettyCode($storage = null)
    {
        return $this->getParserData($storage)['prettyCode'];
    }

    /**
     * Set pretty code
     *
     * @param string $value
     * @param string $storage
     * @return string
     */
    public function setPrettyCode($value, $storage = null)
    {
        return $this->setParserData('prettyCode', $value, $storage);
    }

    /**
     * Get file key
     *
     * @param string $filePath
     */
    public function getFileKey($filePath = null)
    {
        $filePath = ($filePath !== null) ? $filePath : $this->getCurrentFile();

        if (!isset($this->_fileKeys[$filePath])) {
            $this->_fileKeys[$filePath] = (($md5 = @md5_file($filePath)) !== false) ? $md5 : $filePath;
        }

        return $this->_fileKeys[$filePath];
    }

    /**
     * Get parsed files storage; all or for specific file
     *
     * @param mixed $filePath Specific file path, defaults to null for current file
     * @param bool  $getAll   Get all or specific
     * @return array|null
     */
    public function getParserData($filePath = null, $getAll = false)
    {
        if ($getAll === false) {
            $key = $this->getFileKey($filePath);

            return $this->isParsed($key) ? $this->_parsedFiles[$key] : null;
        }

        return !empty($this->_parsedFiles) ? $this->_parsedFiles : null;
    }

    /**
     * Check if parser info exists
     *
     * @param mixed $filePath Specific file path, defaults to null for current file
     * @return bool
     */
    public function isParsed($filePath = null)
    {
        $key = $this->getFileKey($filePath);

        return isset($this->_parsedFiles[$key]);
    }

    /**
     * Set current parser info
     *
     * @param string $key
     * @param mixed  $value
     * @param string $storage Specific file path, defaults to null for current file
     * @return mixed
     */
    public function setParserData($key, $value, $storage = null)
    {
        $storage = $this->getFileKey($storage);

        if (!isset($this->_parsedFiles[$storage])) {
            $this->_parsedFiles[$storage] = [];
        }

        return $this->_parsedFiles[$storage][$key] = $value;
    }

    /**
     * Get parser
     *
     * @return \PhpParser\Parser
     */
    public function getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = new Parser(new LexerEmulative);
        }

        return $this->_parser;
    }

    /**
     * Get pretty printer
     *
     * @return \PhpParser\PrettyPrinter\Standard
     */
    public function getPrettyPrinter()
    {
        if ($this->_prettyPrinter === null) {
            $this->_prettyPrinter = new PrettyPrinter;
        }

        return $this->_prettyPrinter;
    }

    /**
     * Returns current class name
     */
    public static function getClassName()
    {
        return __CLASS__;
    }

    /**
     * Updates parsed files storage with current parsing info
     *
     * @param null $filePath string current file path
     */
    protected function updateParsedFilesStorage($filePath = null)
    {
        $key = $this->getFileKey($filePath);
        $this->_parsedFiles[$key] = array_merge(
            isset($this->_parsedFiles[$key]) ? $this->_parsedFiles[$key] : [],
            [
                'current_file'  => $this->getCurrentFile(),
                'original_code' => $this->getOrgCode(),
                'stmts_tree'    => $this->getStmts(),
            ]
        );
    }

    /**
     * Clean some code
     *
     * @param string $code
     * @return string
     */
    public static function cleanCode($code)
    {
        $code = preg_replace("/(<\?php|\?>)([\s]?|[\s\t]*|[\r\n]*|[\r\n]+)*(\?>|<\?php)/", PHP_EOL, $code); //remove empty php tags
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $code); //remove empty lines
        $code = trim($code);

        return $code;
    }

    /**
     * Create class instance
     *
     * @return PhpFileCombine
     */
    protected static function createInstance()
    {
        $class = self::getClassName();

        return new $class;
    }
}