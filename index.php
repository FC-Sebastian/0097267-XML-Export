<?php
include __DIR__."/autoloader.php";

$controller = "XmlExport";
if (isset($_REQUEST['controller'])) {
    $controller = $_REQUEST['controller'];
}

$controllerObject = new $controller();
if (isset($_REQUEST['action'])) {
    $action = strtolower($_REQUEST['action']);
    if (method_exists($controllerObject, $action)) {
        try {
            $controllerObject->$action();
        } catch (Throwable $exc) {
            $controllerObject->setErrorMessage($exc->getMessage());
        }
    }
}

$controllerObject->render();


