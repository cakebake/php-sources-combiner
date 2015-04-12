<?php

class TraversationTest extends cakebake\combiner\TestCase
{
    public function testTraverser()
    {
//        $this->createFilesystem('testTraverser', [
//            'index.php' => '<?php
//                //start file level-1
//                echo "hello level-1 index!";
//
//                //require level-1
//                require __DIR__ . "/filename1-level-1.php";
//                require __DIR__ . "/filename2-level-1.php";
//                require __DIR__ . "/notfound.php";
//
//                //end file level-1
//            ',
//            'filename1-level-1.php' => '<?php echo "hello filename1-level-1.php";',
//            'filename2-level-1.php' => '<?php echo "hello filename2-level-1.php";',
//        ]);

        $this->createFilesystem('test2LevelsRequire');

        $this->assertFileExists($this->getFilesystemStream('index.php'));

        try {
            $startFile = $this->getFilesystemStream('index.php');
            $outputFilename = self::sanitizeFilename(__METHOD__ . '.php');
            $outPath = $this->tmpDir . DIRECTORY_SEPARATOR . $outputFilename;

            $parser = new PhpFileCombine();
            $parser = $parser->parseFile($startFile)->traverse()->prettyPrint(true)->writeFile($outPath);
            $this->assertFileHasNoErrors($outPath);

        } catch (PhpParser\Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }
}

//Parser
class PhpFileCombine
{
    public $removeComments = false;

    private $_currentFile = null;
    private $_outFile = null;
    private $_parsedFiles = [];
    private $_orgCode = null;
    private $_prettyCode = null;
    private $_stmts = [];
    private $_parser = null;
    private $_traverser = null;
    private $_prettyPrinter = null;

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

        if (@file_exists($file) !== true)
            return false;

        $this->_currentFile = $file;
        $this->_orgCode = file_get_contents($this->getCurrentFile());
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
    * Get parsed files storage; all or for specific file
    *
    * @param bool $getAll Get all or specific
    * @param mixed $key Specific file path, defaults for current file
    * @return array|null
    */
    public function getParsedFilesStorage($getAll = true, $key = null)
    {
        if ($getAll === false) {
            if ($key === null) {
                $key = $this->getCurrentFile();
            }
            return isset($this->_parsedFiles[$key]) ? $this->_parsedFiles[$key] : null;
        }

        return !empty($this->_parsedFiles) ? $this->_parsedFiles : null;
    }

    /**
    * Get parser
    * @return \PhpParser\Parser
    */
    public function getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative);
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
            $this->_traverser = new \PhpParser\NodeTraverser;
        }

        $this->_traverser->addVisitor(new IncludeNodeVisitor($this));
        $this->_traverser->addVisitor(new CommentsNodeVisitor($this));

        return $this->_traverser;
    }

    /**
    * Get pretty printer
    * @return \PhpParser\PrettyPrinter\Standard
    */
    public function getPrettyPrinter()
    {
        if ($this->_prettyPrinter === null) {
            $this->_prettyPrinter = new \PhpParser\PrettyPrinter\Standard;
        }

        return $this->_prettyPrinter;
    }

    /**
    * Updates parsed files storage with current parsing info
    */
    protected function updateParsedFilesStorage()
    {
        $this->_parsedFiles[$this->getCurrentFile()] = array_merge(
            isset($this->_parsedFiles[$this->getCurrentFile()]) ? $this->_parsedFiles[$this->getCurrentFile()] : [],
            [
                'current_file' => $this->getCurrentFile(),
                'original_code' => $this->getOrgCode(),
                'stmts_tree' => $this->getStmts(),
            ]
        );
    }
}



//comments node visitor

class CommentsNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
    private $_phpFileCombineInstance = null;

    public function __construct(PhpFileCombine $phpFileCombineInstance)
    {
        $this->_phpFileCombineInstance = $phpFileCombineInstance;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        //object(PhpParser\Comment) (2 elements)
        if ($node instanceof \PhpParser\Comment) {
            return false;
        }

//        if (($currentFile = $this->getCurrentFile()) !== null) {
//            if ($node instanceof \PhpParser\Node\Expr\Include_) {
//
//                $includeFile = self::getIncludeFile(
//                    $node->expr,
//                    [
//                        '__DIR__' => dirname($currentFile),
//                        '__FILE__' => $currentFile,
//                    ]
//                );
//
//                if (($parseFile = $this->getPhpFileCombine()->parseFile($includeFile)) === false)
//                    return false;
//
//                return $parseFile->traverse()->getStmts();
//            }
//        }
    }

    /**
    * Get current file
    * @return string
    */
    protected function getCurrentFile()
    {
        return $this->getPhpFileCombine()->getCurrentFile();
    }

    /**
    * Get combiner instance
    * @return PhpFileCombine
    */
    protected function getPhpFileCombine()
    {
        return $this->_phpFileCombineInstance;
    }
}




//include node visitor

class IncludeNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
    private $_phpFileCombineInstance = null;

    public function __construct(PhpFileCombine $phpFileCombineInstance)
    {
        $this->_phpFileCombineInstance = $phpFileCombineInstance;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        if (($currentFile = $this->getCurrentFile()) !== null) {
            if ($node instanceof \PhpParser\Node\Expr\Include_) {

                $includeFile = self::getIncludeFile(
                    $node->expr,
                    [
                        '__DIR__' => dirname($currentFile),
                        '__FILE__' => $currentFile,
                    ]
                );

                if (($parseFile = $this->getPhpFileCombine()->parseFile($includeFile)) === false)
                    return false;

                return $parseFile->traverse()->getStmts();
            }
        }
    }

    public function getIncludeFile(\PhpParser\Node $node)
    {
        switch (get_class($node)) {
            case 'PhpParser\Node\Scalar\MagicConst\Dir':
                return dirname($this->getCurrentFile());
                break;

            case 'PhpParser\Node\Scalar\MagicConst\File':
                return $this->getCurrentFile();
                break;

            case 'PhpParser\Node\Expr\BinaryOp\Concat':

                return self::getIncludeFile($node->left) . self::getIncludeFile($node->right);
                break;

            case 'PhpParser\Node\Expr\FuncCall':

                if (!function_exists(($function = (string)$node->name))) {
                    throw new Exception("Function \"$function\" does not exist.");
                }
                $args = [];
                foreach($node->args as $k => $i) {
                    $args[] = self::getIncludeFile($i->value);
                }
                return call_user_func_array($function, $args);
                break;

            default:

                return $node->value;
        }
    }

    /**
    * Get current file
    * @return string
    */
    protected function getCurrentFile()
    {
        return $this->getPhpFileCombine()->getCurrentFile();
    }

    /**
    * Get combiner instance
    * @return PhpFileCombine
    */
    protected function getPhpFileCombine()
    {
        return $this->_phpFileCombineInstance;
    }
}

