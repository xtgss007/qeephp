<?php

class Q_CreateDirFailedException extends QException
{
    public $dir;

    function __construct($dir)
    {
        $this->dir = $dir;
        parent::__construct(__('Create dir "%s" failed.', $dir));
    }
}

