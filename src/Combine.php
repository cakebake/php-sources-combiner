<?php

namespace cakebake\combiner;

use cakebake\combiner\PhpFileCombine;

/**
* @param array $conf Defaults to ['startFile' => null, 'outputDir' => null, 'outputFile' => null, 'removeComments' => false, ]
* @return object cakebake\combiner\Combine
*/
class Combine
{
    private $conf = [];

    public $phpFileCombine = null;

    public function __construct(array $conf = [])
    {
        $this->setConfiguration($conf);
        $this->phpFileCombine();
    }

    protected function phpFileCombine()
    {
        $this->phpFileCombine = new PhpFileCombine($this->conf);
    }

    protected function setConfiguration(array $conf)
    {
        $this->conf = array_merge([
            'startFile' => null,
            'outputDir' => null,
            'outputFile' => null,
            'removeComments' => false,
            'removeDebugInfo' => false,
        ], $conf);
    }
}