<?php

class QException_FileNotFound extends QException
{
    public $required_filename;

    function __construct($filename)
    {
        $this->required_filename = $filename;
        parent::__construct(__('File "%s" not found.', $filename));
    }
}
