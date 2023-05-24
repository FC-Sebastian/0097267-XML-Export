<?php

function autoload($sClass) {
    $aFolders = [
        'Application/Classes',
        'Application/Controllers',
        'Application/Models',
    ];
    foreach ($aFolders as $sFolder) {
        $sPath = __DIR__."/".$sFolder."/".$sClass.".php";
        if (file_exists($sPath)) {
            require_once $sPath;
            return;
        }
    }
    throw new Exception("Class ".$sClass." not found!");
}

spl_autoload_register('autoload');