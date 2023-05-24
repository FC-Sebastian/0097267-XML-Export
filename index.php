<?php
include __DIR__."/autoloader.php";

$sController = "XmlExport";
if (isset($_REQUEST['controller'])) {
    $sController = $_REQUEST['controller'];
}

$oControllerObject = new $sController();
if (isset($_REQUEST['action'])) {
    $sAction = strtolower($_REQUEST['action']);
    if (method_exists($oControllerObject, $sAction)) {
        try {
            $oControllerObject->$sAction();
        } catch (Throwable $exc) {
            $oControllerObject->setErrorMessage($exc->getMessage());
        }
    }
}

$oControllerObject->render();


