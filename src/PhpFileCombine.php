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
    private $_orgCode = null;
    private $_prettyCode = null;
    private $_stmts = [];
    private $_parser = null;
    private $_traverser = null;
    private $_prettyPrinter = null;

    private static $_self = null;

    /**
    * Static constructor
    */
    public static function init()
    {
        return self::createInstance();
    }

    /**
    * Write the output file
    *
    * @param string $filename
    * @param string $code
    * @return PhpFileCombine
    */
    public function writeFile($filename = null, $code = null)
    {
        if ($filename !== null) {
            $this->_outFile = $filename;
        }
        if ($code !== null) {
            $this->_prettyCode = $code;
        }

        file_put_contents($this->getOutputFile(), $this->getPrettyCode(), LOCK_EX);
        $this->updateParsedFilesStorage();

        return $this;
    }

    /**
    * Pretty prints the stmts tree
    *
    * @param bool $finalPrint Adds php tags
    * @param array $stmts
    * @return PhpFileCombine
    */
    public function prettyPrint($finalPrint = false, array $stmts = [])
    {
        if (!empty($stmts)) {
            $this->_stmts = $stmts;
        }

        if ($finalPrint === true) {
            $this->_prettyCode = $this->getPrettyPrinter()->prettyPrintFile($this->getStmts());
        } else {
            $this->_prettyCode = $this->getPrettyPrinter()->prettyPrint($this->getStmts());
        }

        $this->_prettyCode = $this->cleanCode($this->_prettyCode);
        $this->updateParsedFilesStorage();

        return $this;
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
    * Stmts tree setter from file
    *
    * @param string $file
    * @return PhpFileCombine
    */
    public function parseFile($file = null)
    {
        $file = ($file !== null) ? $file : $this->_currentFile;

        if (($orgCode = @trim(@file_get_contents($file))) === false ||
            empty($orgCode)) {

            return false;
        }

        $this->_currentFile = $file;
        $this->_orgCode = $orgCode;
        $this->updateParsedFilesStorage();

        return $this->parse($this->getOrgCode());
    }

    /**
    * Stmts tree setter from code
    *
    * @param string $code
    * @return PhpFileCombine
    */
    public function parse($code = null)
    {
        if (trim($code) == '')
            return false;

        if ($code !== null) {
            $this->_orgCode = $code;
        }

        $this->_stmts = $this->getParser()->parse($this->getOrgCode());
        $this->updateParsedFilesStorage();

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
            $this->_stmts = $stmts;
        }

        $this->_stmts = $this->getTraverser()->traverse($this->getStmts());
        $this->updateParsedFilesStorage();

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
    * Get output file
    * @return string
    */
    public function getOutputFile()
    {
        return $this->_outFile;
    }

    /**
    * Get stmts tree
    * @return array Stmts tree
    */
    public function getStmts()
    {
        return $this->_stmts;
    }

    /**
    * Get original code
    * @return string
    */
    public function getOrgCode()
    {
        return $this->_orgCode;
    }

    /**
    * Get pretty code
    * @return string
    */
    public function getPrettyCode()
    {
        return $this->_prettyCode;
    }
    
    /**
    * Get file key
    * 
    * @param mixed $filePath
    */
    public function getFileKey($filePath = null)
    {
        $filePath = ($filePath !== null) ? $filePath : $this->getCurrentFile();
        
        return (($md5 = md5_file($filePath)) !== false) ? $md5 : $filePath;
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
    protected function setParserData($key, $value, $storage = null)
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
    */
    protected function updateParsedFilesStorage()
    {
        $key = $this->getFileKey();
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