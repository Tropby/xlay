# XLay

This library is based on the reverse engeneering of Sergey-Raevskiy (https://github.com/sergey-raevskiy/xlay). It can render a layout (with some restrictions).

The Software SprintLayout 6 is published and sold by [Abacom](https://www.electronic-software-shop.com/elektronik-software/sprint-layout-60.html?language=de
). I do not have anything to do with this company.

The core feature of this library is to analyze a `*.lay6` file and draw its content in an image (SVG). It is also working with macro files.

## Short Example

### Render board 0 from a lay6-file
``` php
<?php

require_once("../src/xlay.inc.php");

$renderer = new \XLay\Renderer\SVG();
$file = \XLay\XLay::loadLay6("test.lay6");
$svg = $renderer->render($file->getBoards()[0]);
echo $svg;

```

![](res/img/output.svg)

### Render a macro

``` php
<?php

require_once("../src/xlay.inc.php");

$renderer = new \XLay\Renderer\SVG();
$file = \XLay\XLay::loadMacro("test.lmk");
$svg = $renderer->render($file, null, \XLay\Layer::LAYERS_DEFAULT_ORDER,[0,0,0],[$file->getOffsetX(),$file->getOffsetY()]);
echo $svg;

```

## ToDo

[HERE](TODO.md)