<?php
// $Id$

class Q_IllegalFilenameException extends QException
{
    public $required_filename;

    function __construct($filename)
    {
        $this->required_filename = $filename;
        parent::__construct(__('Security check: Illegal character in filename "%s".', $filename));
    }
}

