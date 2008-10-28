<?php

class Controller_Static extends QController_Abstract
{
	function actionCss()
	{
        return $this->_output('all_in_one.css', 'text/css');
    }

	function actionJS()
	{
        return $this->_output('jquery.js', 'text/javascript');
	}

    protected function _output($filename, $mime)
    {
		$output = new QView_Output($filename, $mime);
		$output->addFile($this->app()->ROOT_DIR() . "/static/{$filename}");
		$output->enableClientCache(true);

		return $output;
	}

}
