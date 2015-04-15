<?php

namespace cakebake\combiner\NodeVisitor;

use \Exception;

class IncludeNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
    private $_phpFileCombineInstance = null;
    private $_currentFile = null;

    public function __construct(\cakebake\combiner\PhpFileCombine $phpFileCombineInstance, $currentFile)
    {
        $this->_phpFileCombineInstance = $phpFileCombineInstance;
        $this->_currentFile = $currentFile;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        if (($parentFile = $this->getCurrentFile()) !== null) {
            if ($node instanceof \PhpParser\Node\Expr\Include_) {

                $currentFile = self::getIncludeFile(
                    $node->expr,
                    [
                        '__DIR__' => dirname($parentFile),
                        '__FILE__' => $parentFile,
                    ]
                );

                if ($node->type == \PhpParser\Node\Expr\Include_::TYPE_INCLUDE_ONCE ||
                    $node->type == \PhpParser\Node\Expr\Include_::TYPE_REQUIRE_ONCE) {

                    if ($this->getPhpFileCombine()->isParsed($currentFile))
                        return \PhpParser\NodeTraverser::REMOVE_NODE;
                }

                if (($this->getPhpFileCombine()->parseFile($currentFile, $parentFile)) === false)
                    return \PhpParser\NodeTraverser::REMOVE_NODE;

                $stmts = $this->getPhpFileCombine()->traverse()->getStmts();
                
                if (isset($stmts[0])) {
                    $stmts[0]->setAttribute('comments', array_merge(
                        $node->getAttribute('comments', []),
                        $stmts[0]->getAttribute('comments', [])
                    ));
                }
                
                return $stmts;
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
        return $this->_currentFile;
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