# XLay

This library is based on the reverse engeneering of Sergey-Raevskiy (https://github.com/sergey-raevskiy/xlay). It can render a layout (with some restrictions).

The Software SprintLayout 6 is published and sold by [Abacom](https://www.electronic-software-shop.com/elektronik-software/sprint-layout-60.html?language=de
). I do not have anything to do with this company. 

The core feature of this library is to analyze a `*.lay6` file and draw its content in an image. By now only drawing of a pixel image is supported. 

## Short Example

``` php
<?php

require_once("../src/xlay.inc.php");

$xlay = new \XLay\XLay();
$xlay->load("test.lay6");

$renderer = new \XLay\Renderer\Image();
$renderer->render($xlay->getFile()->getBoards()[0]);
```

![](res/img/output.png)