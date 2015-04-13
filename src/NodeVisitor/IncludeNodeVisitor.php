<?php

namespace cakebake\combiner\NodeVisitor;

class IncludeNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
    private $_phpFileCombineInstance = null;

    public function __construct(\cakebake\combiner\PhpFileCombine $phpFileCombineInstance)
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