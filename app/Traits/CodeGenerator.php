<?php

namespace App\Traits;

trait CodeGenerator
{

    /**
     * Generate incremental code like GRP-A1, GRP-A2...
     *
     * @return string
     */
    abstract public function generateCode();
}
