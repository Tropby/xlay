<?php

namespace XLay;

class Layer
{
    const LAYERS_DEFAULT_ORDER = [\XLay\Layer::C1,\XLay\Layer::C2,\XLay\Layer::S1,\XLay\Layer::S2,\XLay\Layer::I1,\XLay\Layer::I2,\XLay\Layer::O];
    const LAYERS_TOP_ONLY_ORDER = [\XLay\Layer::C1,\XLay\Layer::S1,\XLay\Layer::O];
    const LAYERS_BOTTOM_ONLY_ORDER = [\XLay\Layer::C2,\XLay\Layer::S2,\XLay\Layer::O];

    public const C1 = 1;
    public const S1 = 2;
    public const C2 = 3;
    public const S2 = 4;
    public const I1 = 5;
    public const I2 = 6;
    public const O = 7;
    public const M = 8;
}
