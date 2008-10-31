<?php

class Q_FileNotReadableException extends QException
{
    public $filename;

    function __construct($filename)
    {
        $this->filename = $filename;
        // LC_MSG: File "%s" not readable.
        parent::__construct(__('File "%s" not readable.', $filename));
    }
}

