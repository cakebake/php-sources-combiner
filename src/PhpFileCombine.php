<?php

namespace cakebake\combiner;

use Exception;

class PhpFileCombine extends \PhpParser\PrettyPrinter\Standard
{
    protected $startFile = null;

    protected $outputDir = null;

    protected $outputFile = 'combined.php';

    protected $removeComments = false;

    private $_baseDir = null;

    private $_parser = null;

    private $_currentFile = null;

    /**
    * Combines the start file with its includes and namespaces
    * @return object cakebake\combiner\PhpFileCombine
    */
    public function __construct(array $conf) {
        parent::__construct();
        $this->setConfiguration($conf);
        $this->combine();
    }

    protected function combine()
    {
        $parsedFile = $this->parseFile();
        $code = '<?php' . PHP_EOL . $parsedFile['code'];

        return (file_put_contents($this->outputDir . DIRECTORY_SEPARATOR . $this->outputFile, $code, LOCK_EX) !== false) ? true : false;
    }

    protected function parseFile($file = null)
    {
        $file = ($file !== null) ? $file : $this->startFile;
        if (($fileContent = @file_get_contents($file)) === false || empty($fileContent))
            throw new Exception("File \"{$file}\" is empty or not readable.");

        $tree = $this->getParser()->parse($fileContent);

//        if (isset($tree[0]) && ($tree[0] instanceof \PhpParser\Node\Stmt\Namespace_) === false) {
//            $tree = [new \PhpParser\Node\Stmt\Namespace_(null, $tree)];
//        }

        return $this->_currentFile = [
            'fileContent' => $fileContent,
            'code' => $this->prettyPrint($tree),
            'tree' => $tree,
        ];
    }

    protected function getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = new \PhpParser\Parser(new \PhpParser\Lexer);
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
        $conf['_baseDir'] = isset($conf['startFile']) ? @dirname($conf['startFile']) : null;

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

        if (array_key_exists($node->type, $map)) {

            $file = self::getIncludeValue($node->expr, [
                '__DIR__' => dirname($this->startFile),
                '__FILE__' => $this->startFile,
            ]);

            if (empty($file) || !file_exists($file)) {
                throw new Exception("{$map[$node->type]} file \"$file\" not found in \"{$this->startFile}\" line {$node->getLine()}. Info: Do not use relative paths.");
            }

            $parsedFile = $this->parseFile($file);

            return $parsedFile['code'] . PHP_EOL . '//EOF';
        }

        return parent::pExpr_Include($node);
    }

    public static function getIncludeValue($node, array $constants = [])
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
}