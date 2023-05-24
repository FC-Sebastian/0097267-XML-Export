<?php
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            <?php if (isset($sTitle)) {echo $sTitle;}?>
        </title>
        <link rel="stylesheet" href="<?php echo $sUrl;?>">
    </head>
    <body class="bg-primary bg-opacity-10">
    <?php if ($oController->getError() !== false):?>
        <div class="bg-danger bg-opacity-25 border border-5 border-danger border-end-0 border-start-0">
            <div class="w-50 m-auto">
                <h1 class="fw-bold fs-2">Error:</h1>
                <p class="fs-5">
                    <?php echo $oController->getError()?>
                </p>
            </div>
        </div>
    <?php endif;?>
        <div class="container  min-vh-100 shadow bg-white pt-3">

