@REM<?php
@REM ==&#39;
@php -d safe_mode=Off %0 %1 %2
@goto :EOF
@REM&#39;;?>
<?php

set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . '/include/phpunit');

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

require 'PHPUnit/TextUI/Command.php';

