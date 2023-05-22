<?php
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            <?php if (isset($title)) {echo $title;}?>
        </title>
        <link rel="stylesheet" href="<?php echo $url;?>">
        <link rel="stylesheet" href="<?php echo $controller->getUrl("css/myCss.css");?>">
        <script src="<?= $controller->getUrl("js/jquery-3.6.3.js") ?>"></script>
    </head>
    <body class="bg-primary bg-opacity-10" onload="<?php echo $controller->getOnload()?>">
    <nav class="navbar navbar-expand bg-primary bg-gradient shadow">
        <div class="container-fluid justify-content-xl-end justify-content-lg-end justify-content-md-end justify-content-center">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-white" href="<?php echo $controller->getUrl("index.php?controller=Edit")?>">New Azubi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link link-light" href="<?php echo $controller->getUrl("index.php?controller=AzubiList")?>">Liste</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link link-light" href="<?php echo $controller->getUrl("index.php?controller=Team")?>">Team</a>
                </li>
            </ul>
        </div>
    </nav>
    <?php if ($controller->getError() !== false):?>
        <div class="bg-danger bg-opacity-25 border border-5 border-danger border-end-0 border-start-0">
            <div class="w-50 m-auto">
                <h1 class="fw-bold fs-2">Error:</h1>
                <p class="fs-5">
                    <?php echo $controller->getError()?>
                </p>
            </div>
        </div>
    <?php endif;?>
        <div class="container  min-vh-100 shadow bg-white pt-3">

