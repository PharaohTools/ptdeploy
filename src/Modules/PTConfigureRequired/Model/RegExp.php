<?php

namespace Model;

class RegExp
{

    public $regexp;

    public function __construct($regexp)
    {
        $this->regexp = $regexp;
    }

    public function __toString()
    {
        return "RegExp({$this->regexp})";
    }
}
