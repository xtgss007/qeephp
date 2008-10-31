<?php

class Q_RemoveDirFailedException extends QException
{
    public $dir;

    function __construct($dir)
    {
        $this->dir = $dir;
        parent::__construct(__('Remove dir "%s" failed.', $dir));
    }
}


