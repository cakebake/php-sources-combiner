<?php

namespace cakebake\combiner;

use Exception;

class PhpFileCombine extends \PhpParser\PrettyPrinter\Standard
{
    protected $startFile = null;

    protected $outputDir = null;

    protected $outputFile = 'combined.php';

    protected $removeComments = false;

    protected $removeDebugInfo = false;

    private $_parser = null;

    private $_currentFile = null;

    private $_parsedFiles = [];

    /**
    * Combines the start file with its includes and namespaces
    * @return object cakebake\combiner\PhpFileCombine
    */
    public function __construct(array $conf) {
        parent::__construct();
        $this->setConfiguration($conf);
        $this->combine();
    }

    /**
    * Returns all parsed files informations (startfile, includes, requires, ...)
    * @return array Parsed files indexed by file path with value: ['fileContent' => 'Org code', 'tree' => 'Syntax tree from php parser', 'code' => 'Pretty printed code']
    */
    public function getParsedFiles()
    {
        return $this->_parsedFiles;
    }

    protected function combine()
    {
        $code = '<?php' . PHP_EOL . $this->cleanCode($this->parseFile($this->startFile));

        return (file_put_contents($this->outputDir . DIRECTORY_SEPARATOR . $this->outputFile, $code, LOCK_EX) !== false) ? true : false;
    }

    protected function parseFile($file)
    {
        if (($fileContent = @trim(@file_get_contents($file))) === false ||
            empty($fileContent)) {
            return null;
        }

        if (isset($this->_parsedFiles[$file]))
            return $this->_parsedFiles[$file]['code'];

        $this->_parsedFiles[$file] = [
            'fileContent' => $fileContent,
            'tree' => null,
            'code' => null,
        ];

        $tree = $this->_parsedFiles[$file]['tree'] = $this->getParser()->parse($fileContent);

//        if (isset($tree[0]) && ($tree[0] instanceof \PhpParser\Node\Stmt\Namespace_) === false) {
//            $tree = [new \PhpParser\Node\Stmt\Namespace_(null, $tree)];
//        }

        $lastFile = $this->_currentFile;
        $this->_currentFile = $file;
        $code = $this->_parsedFiles[$file]['code'] = $this->prettyPrint($tree);
        $this->_currentFile = $lastFile;

        return $code;
    }

    protected function getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative);
        }

        return $this->_parser;
    }

    /**
    * Set configuration propertys
    *
    * @param array $conf
    */
    protected function setConfiguration(array $conf)
    {
        foreach ($conf as $k => $i) {
            if (property_exists($this, $k)) {
                if (empty($i) && !is_bool($i))
                    throw new Exception("Missing \"$k\" configuration property.");

                if (preg_match('/path|dir|startFile/i', $k) && !@file_exists($i))
                    throw new Exception("File path \"$i\" does not exist. Please check your configuration related to \"$k\" property.");

                $this->{$k} = $conf[$k];
            }
        }
    }

    /**
    * @inheritdoc
    */
    public function pComments(array $comments)
    {
        return ($this->removeComments === true) ? null : parent::pComments($comments);
    }

    /**
    * @inheritdoc
    */
    public function pExpr_Include(\PhpParser\Node\Expr\Include_ $node)
    {
        static $map = [
            \PhpParser\Node\Expr\Include_::TYPE_INCLUDE      => 'include',
            \PhpParser\Node\Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            \PhpParser\Node\Expr\Include_::TYPE_REQUIRE      => 'require',
            \PhpParser\Node\Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
        ];

        $file = $this->getIncludeValue($node->expr, [
            '__DIR__' => dirname($this->_currentFile),
            '__FILE__' => $this->_currentFile,
        ]);

        if (empty($file) || !file_exists($file)) {
            throw new Exception("{$map[$node->type]} file \"$file\" not found in \"{$this->_currentFile}\" line {$node->getLine()}.");
        }

        if ($node->type == \PhpParser\Node\Expr\Include_::TYPE_INCLUDE_ONCE ||
            $node->type == \PhpParser\Node\Expr\Include_::TYPE_REQUIRE_ONCE) {

            if (isset($this->_parsedFiles[$file]) || $this->_currentFile == $file)
                return null;
        }

        $code = '';
        if (($parsed = $this->parseFile($file)) !== null) {
            $code .= ($this->removeDebugInfo !== true) ? "#" . PHP_EOL . "# --- START {$map[$node->type]}('$file') in \"{$this->_currentFile}\" line {$node->getLine()} ---" . PHP_EOL . "#" . PHP_EOL : null;
            $code .= $parsed;
            $code .= ($this->removeDebugInfo !== true) ? PHP_EOL : '';

        }

        return $code . '# --- END';
    }

    public function getIncludeValue($node, array $constants = [])
    {
        $nodeClass = get_class($node);

        switch ($nodeClass) {
            case 'PhpParser\Node\Scalar\MagicConst\Dir':

                if (!isset($constants['__DIR__']))
                    throw new Exception("Constant \"__DIR__\" is missing.");

                return $constants['__DIR__'];
                break;

            case 'PhpParser\Node\Scalar\MagicConst\File':

                if (!isset($constants['__FILE__']))
                    throw new Exception("Constant \"__FILE__\" is missing.");

                return $constants['__FILE__'];
                break;

            case 'PhpParser\Node\Expr\BinaryOp\Concat':

                return self::getIncludeValue($node->left, $constants) . self::getIncludeValue($node->right, $constants);
                break;

            case 'PhpParser\Node\Expr\FuncCall':

                if (!function_exists(($function = (string)$node->name)))
                    throw new Exception("Function \"$function\" does not exist.");

                $args = [];
                foreach($node->args as $k => $i) {
                    $args[] = self::getIncludeValue($i->value, $constants);
                }

                return call_user_func_array($function, $args);
                break;

            default:

                return $node->value;
        }
    }

    protected function cleanCode($code)
    {
        $code = ($this->removeDebugInfo === true) ? str_replace(['# --- END;'], '', $code) : $code; //remove end markers to avoid unnecessary semicolon
        $code = preg_replace("/<\?php([\s]?|[\s\t]*|[\r\n]*|[\r\n]+)*\?>/", PHP_EOL, $code); //remove empty php tags
        $code = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $code); //remove empty lines
        //$code = $code . '<?php echo "syntaxerror'; //test error in output file

        return $code;
    }
}