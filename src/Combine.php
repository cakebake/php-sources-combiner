<?php

namespace cakebake\combiner;

use cakebake\combiner\PhpFileCombine;

class Combine
{
    private $conf = [];

    public function __construct($conf = [])
    {
        $this->setConfiguration($conf);

        print_r($this->conf);
    }

    protected function setConfiguration($conf)
    {
        $this->conf = array_merge([
        ], $conf);
    }
}