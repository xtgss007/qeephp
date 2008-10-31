<?php

class Q_CreateFileFailedException extends QException
{
    public $ex_filename;

    function __construct($filename)
    {
        $this->ex_filename = $filename;
        parent::__construct(__('Create file "%s" failed.', $filename));
    }
}


