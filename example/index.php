<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../src/xlay.inc.php");

$xlay = new \XLay\XLay();
$xlay->load("test.lay6");

$renderer = new \XLay\Renderer\Image();
$renderer->render($xlay->getFile()->getBoards()[0], \XLay\Layer::LAYERS_DEFAULT_ORDER);
