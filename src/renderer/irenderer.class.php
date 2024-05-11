<?php

namespace XLay\Renderer;

interface IRenderer
{
    public function render(
        & $board,
        $filename = null,
        $layers = \XLay\Layer::LAYERS_DEFAULT_ORDER,
        $offset = [0,0],
        $flipHorizontal = false
    );

    public function setColorScheme(array $colorScheme);

}
