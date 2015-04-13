<?php

namespace cakebake\combiner;

use PhpParser\Parser;
use PhpParser\Lexer\Emulative as LexerEmulative;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use PhpParser\NodeTraverser;
use cakebake\combiner\NodeVisitor\IncludeNodeVisitor;
use Exception;

/**
* PhpFileCombine
* @example PhpFileCombine::init()->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);
*/
class PhpFileCombine
{
    private $_currentFile = null;
    private $_outFile = null;
    private $_parsedFiles = [];
    private $_parser = null;
    private $_traverser = null;
    private $_prettyPrinter = null;
    private static $_self = null;

    /**
    * Static class constructor
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
    * @param bool $finalPrint Adds php tags
    * @param array $stmts Stmts tree
    * @return PhpFileCombine
    */
    public function prettyPrint($finalPrint = false, array $stmts = [])
    {
        if (!empty($stmts)) {
            $this->setStmts($stmts);
        }

        if ($finalPrint === true) {
            $code = $this->setPrettyCode($this->getPrettyPrinter()->prettyPrintFile($this->getStmts()));
        } else {
            $code = $this->setPrettyCode($this->getPrettyPrinter()->prettyPrint($this->getStmts()));
        }

        $this->setPrettyCode($this->cleanCode($code));

        return $this;
    }

    /**
    * Stmts tree setter from file
    *
    * @param string $file
    * @return PhpFileCombine
    */
    public function parseFile($file)
    {
        if (($orgCode = @trim(@file_get_contents($file))) === false ||
            empty($orgCode)) {

            return false;
        }

        $this->setCurrentFile($file);
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

        $this->setStmts($this->getTraverser()->traverse($this->getStmts()));

        return $this;
    }

    /**
    * Get current file
    * @return string
    */
    public function getCurrentFile()
    {
        return $this->_currentFile;
    }

    /**
    * Set current file path
    *
    * @param string $currentFile
    */
    public function setCurrentFile($value, $storage = null)
    {
        $this->_currentFile = $value;
        $this->setParserData('orgFile', $value, $storage);
    }

    /**
    * Get output file
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
    */
    public function setOutputFile($value)
    {
        return $this->_outFile = $value;
    }

    /**
    * Get stmts tree
    * @return array Stmts tree
    */
    public function getStmts($storage = null)
    {
        return $this->getParserData($storage)['stmts'];
    }

    /**
    * Set stmts tree to storage
    *
    * @param array $value
    * @param string $storage
    */
    public function setStmts(array $value, $storage = null)
    {
        return $this->setParserData('stmts', $value, $storage);
    }

    /**
    * Get original code from storage
    *
    * @return string
    */
    public function getOrgCode($storage = null)
    {
        return $this->getParserData($storage)['orgCode'];
    }

    /**
    * Set original code to storage
    *
    * @param string $orgCode
    */
    public function setOrgCode($value, $storage = null)
    {
        return $this->setParserData('orgCode', $value, $storage);
    }

    /**
    * Get pretty code
    *
    * @return string
    */
    public function getPrettyCode($storage = null)
    {
        return $this->getParserData($storage)['prettyCode'];
    }

    public function setPrettyCode($value, $storage = null)
    {
        return $this->setParserData('prettyCode', $value, $storage);
    }

    /**
    * Get file key
    *
    * @param mixed $filePath
    */
    public function getFileKey($filePath = null)
    {
        $filePath = ($filePath !== null) ? $filePath : $this->getCurrentFile();

        return (($md5 = @md5_file($filePath)) !== false) ? $md5 : $filePath;
    }

    /**
    * Get parsed files storage; all or for specific file
    *
    * @param mixed $filePath Specific file path, defaults to null for current file
    * @param bool $getAll Get all or specific
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
    * @param mixed $filePath Specific file path, defaults to null for current file
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
    * @param mixed $value
    * @param string $storage Specific file path, defaults to null for current file
    * @return value
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
    * Get node traverser and its visitors
    * @return \PhpParser\NodeTraverser
    */
    public function getTraverser()
    {
        if ($this->_traverser === null) {
            $this->_traverser = new NodeTraverser;
        }

        $this->_traverser->addVisitor(new IncludeNodeVisitor($this));

        return $this->_traverser;
    }

    /**
    * Get pretty printer
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
    * @property $filePath string current file path
    */
    protected function updateParsedFilesStorage($filePath = null)
    {
        $key = $this->getFileKey($filePath);
        $this->_parsedFiles[$key] = array_merge(
            isset($this->_parsedFiles[$key]) ? $this->_parsedFiles[$key] : [],
            [
                'current_file' => $this->getCurrentFile(),
                'original_code' => $this->getOrgCode(),
                'stmts_tree' => $this->getStmts(),
            ]
        );
    }

    /**
    * Clean some code
    *
    * @param string $code
    */
    protected function cleanCode($code)
    {
        /*$code = preg_replace("/(<\?php|\?>)([\s]?|[\s\t]*|[\r\n]*|[\r\n]+)*(\?>|<\?php)/", PHP_EOL, $code); //remove empty php tags*/
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $code); //remove empty lines
        $code = trim($code);

        return $code;
    }

    /**
    * Create class instance
    */
    protected static function createInstance()
    {
        if (self::$_self === null) {
            $class = self::getClassName();
            self::$_self = new $class;
        }

        return self::$_self;
    }
}