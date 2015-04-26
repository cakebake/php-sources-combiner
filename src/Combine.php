<?php

namespace cakebake\combiner;

/**
 * @param array $conf Defaults to ['startFile' => null, 'outputDir' => null, 'outputFile' => null, 'removeComments' =>
 *                    false, ]
 * @return object cakebake\combiner\Combine
 */
class Combine
{
    public $phpFileCombine = null;
    private $conf = [];

    public function __construct(array $conf = [])
    {
        $this->setConfiguration($conf);
        $this->phpFileCombine();
    }

    protected function setConfiguration(array $conf)
    {
        $this->conf = array_merge([
            'startFile'       => null,
            'outputDir'       => null,
            'outputFile'      => null,
            'removeComments'  => false,
            'removeDebugInfo' => false,
        ], $conf);
    }

    protected function phpFileCombine()
    {
        $this->phpFileCombine = new PhpFileCombine($this->conf);
    }
}