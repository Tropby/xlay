<?php

namespace XLay;

class Layer
{
    const LAYERS_DEFAULT_ORDER = [\XLay\Layer::C2,\XLay\Layer::C1,\XLay\Layer::S1,\XLay\Layer::S2,\XLay\Layer::I1,\XLay\Layer::I2,\XLay\Layer::O];
    const LAYERS_TOP_ONLY_ORDER = [\XLay\Layer::C1,\XLay\Layer::S1,\XLay\Layer::O];
    const LAYERS_BOTTOM_ONLY_ORDER = [\XLay\Layer::C2,\XLay\Layer::S2,\XLay\Layer::O];
    const LAYERS_LASER_CUT = [\XLay\Layer::I1,\XLay\Layer::I2];

    const COLORS_DEFAULT = [
        Layer::B => [0,0,0],
        Layer::B | Layer::COPPER => [0,0,0],
        Layer::S1 => [255,0,0],
        Layer::S1 | Layer::COPPER => [255,0,0],
        Layer::C1 => [30,106,249],
        Layer::C2 => [0,186,0],
        Layer::S2 => [225,215,4],
        Layer::S2 | Layer::COPPER => [225,215,4],
        Layer::I1 => [194,124,20],
        Layer::I1 | Layer::COPPER => [194,124,20],
        Layer::I2 => [238,182,98],
        Layer::I2 | Layer::COPPER => [238,182,98],
        Layer::O => [255,255,255],
        Layer::O | Layer::COPPER => [255,255,255],
        Layer::M => [81,227,253],

        Layer::C1 | Layer::COPPER => [30,106,249],
        Layer::C2 | Layer::COPPER => [0,186,0],
        Layer::M | Layer::COPPER => [81,227,253],

        Layer::C1 | Layer::GROUND_PLANE => [0,76,219],
        Layer::C2 | Layer::GROUND_PLANE => [0,156,0]
    ];

    const COLORS_FOTO = [
        Layer::B => [0,51,0],        
        Layer::C1 => [11,132,20],
        Layer::S1 => [255,255,255],
        Layer::S1 | Layer::COPPER => [255,255,255],
        Layer::C2 => [11,132,20],
        Layer::S2 => [255,255,255],
        Layer::S2 | Layer::COPPER => [255,255,255],
        Layer::I1 => [0,255,255],
        Layer::I2 => [0,255,255],
        Layer::O => [0,0,0],
        Layer::M => [11,132,20],
        Layer::O | Layer::COPPER => [0,0,0],
        Layer::C1 | Layer::COPPER => [235,190,44],
        Layer::C2 | Layer::COPPER => [235,190,44],
        Layer::M | Layer::COPPER => [235,190,44],
        Layer::C1 | Layer::GROUND_PLANE => [11,132,20],
        Layer::C2 | Layer::GROUND_PLANE => [11,132,20]
    ];

    const COLORS_LASER_CUTTER = [
        Layer::B => [255,255,255],
        Layer::C1 => [0,255,0],
        Layer::S1 => [0,255,0],
        Layer::C2 => [0,255,0],
        Layer::S2 => [0,255,0],
        Layer::I1 => [255,0,0],
        Layer::I2 => [0,0,255],
        Layer::O => [0,255,255],
        Layer::M => [0,255,255],
        Layer::C1 | Layer::COPPER => [0,255,0],
        Layer::C2 | Layer::COPPER => [0,255,0],
        Layer::M | Layer::COPPER => [0,255,0],
        Layer::C1 | Layer::GROUND_PLANE => [0,255,0],
        Layer::C2 | Layer::GROUND_PLANE => [0,255,0]
    ];

    public const B = 0; // Background
    public const C1 = 1;
    public const S1 = 2;
    public const C2 = 3;
    public const S2 = 4;
    public const I1 = 5;
    public const I2 = 6;
    public const O = 7;

    public const M = 8; // Metal Through Pad

    public const COPPER = 64; // Show as Copper (set this bit)
    public const GROUND_PLANE = 128; // Show as Ground Plane (set this bit)

    static function getLayerName(int $layer) : string
    {
        switch($layer)
        {
            case Layer::B: return "BACKGROUND"; break;
            case Layer::C1: return "COPPER1"; break;
            case Layer::C2: return "COPPER2"; break;
            case Layer::C1 | Layer::GROUND_PLANE: return "COPPER1_GROUND_PLANE"; break;
            case Layer::C2 | Layer::GROUND_PLANE: return "COPPER2_GROUND_PLANE"; break;
            case Layer::S1: return "SILK1"; break;
            case Layer::S2: return "SILK2"; break;
            case Layer::I1: return "INNER1"; break;
            case Layer::I2: return "INNER2"; break;
            case Layer::O: return "OUTLINE"; break;
        }
        return "UNKNWON";
    }
}
