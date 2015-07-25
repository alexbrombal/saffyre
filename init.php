<?php

include dirname(__FILE__) . '/core/Saffyre.php';			// Include "Saffyre" class to start with

Saffyre::includePath(dirname(__FILE__));

function __saffyre_autoload($class) {							// autoloads classes based on include_path
    @include_once "$class.php";
}

spl_autoload_register('__saffyre_autoload', true, false);