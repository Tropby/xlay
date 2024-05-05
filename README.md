# XLay

* Abacom
* Renderer für PHP
* Deserilizer of SprintLayout files

``` php
<?php

require_once("../src/xlay.inc.php");

$xlay = new \XLay\XLay();
$xlay->load("test.lay6");

$renderer = new \XLay\Renderer\Image();
$renderer->render($xlay->getFile()->getBoards()[0]);
```
