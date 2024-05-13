<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

if( isset( $_FILES["lay6"] ))
{
    require_once("../../src/xlay.inc.php");

    $filename = uniqid("cache_").".svg";

    $renderer = new \XLay\Renderer\SVG();
    if( isset($_POST["photo"]))
    {
        $renderer->setColorScheme(\XLay\Layer::COLORS_FOTO);
    }

    switch( $_POST["layers"] )
    {
        case "LAYERS_TOP_ONLY_ORDER": $layers = \XLay\Layer::LAYERS_TOP_ONLY_ORDER; break;
        case "LAYERS_BOTTOM_ONLY_ORDER": $layers = \XLay\Layer::LAYERS_BOTTOM_ONLY_ORDER; break;
        default: $layers = \XLay\Layer::LAYERS_DEFAULT_ORDER; break;
    }

    if( isset($_POST["lasercut"]))
    {
        $renderer->setColorScheme(\XLay\Layer::COLORS_LASER_CUTTER);
        $layers = \XLay\Layer::LAYERS_LASER_CUT;
    }

    if( substr($_FILES["lay6"]["name"], -5) == ".lay6" )
    {
        $file = \XLay\XLay::loadLay6($_FILES["lay6"]["tmp_name"]);
        unlink($_FILES["lay6"]["tmp_name"]);
        $svg = $renderer->render($file->getBoards()[0], $filename, $layers, [0,0], isset($_POST["fliph"]));
    }
    else
    {
        $file = \XLay\XLay::loadMacro($_FILES["lay6"]["tmp_name"]);
        unlink($_FILES["lay6"]["tmp_name"]);
        $svg = $renderer->render($file, $filename, $layers, [$file->getOffsetX(),$file->getOffsetY()], isset($_POST["fliph"]));
    }
}

?>
<html>
    <head>
        <title>Sprint Layout Image Creator</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    </head>
    <body>
        <br />
        <div class="container">
        <h1>Upload your SprintLayout File</h1>
        <form method="POST" enctype="multipart/form-data" >
            <br />
            <label class="form-check-label" for="lay6">File (*.lay6/*.lmk)</label>
            <input class="form-control" type="file" name="lay6" /><br />
            <label class="form-check-label" for="layers">Layers</label><br />
            <select class="form-select" name="layers" id="layers">
                <option value="LAYERS_DEFAULT_ORDER">Default</option>
                <option value="LAYERS_TOP_ONLY_ORDER">Top Only</option>
                <option value="LAYERS_BOTTOM_ONLY_ORDER">Bottom Only</option>
            </select><br />
            <input class="form-check-input" type="checkbox" name="photo" id="photo" /> <label class="form-check-label" for="photo">Photo Renderer</label><br /><br />
            <input class="form-check-input" type="checkbox" name="lasercut" id="lasercut" /> <label class="form-check-label" for="lasercut">Laser Cut<br /><i>(Layer: I1 [cut]/I2 [engrave])</i></label><br /><br />
            <input class="form-check-input" type="checkbox" name="fliph" id="fliph" /> <label class="form-check-label" for="fliph">Flip Horizontal</label><br /><br />
            <input class="btn btn-primary" type="submit" value="Convert" name="submit" />
        </form>
        <hr />
<?php
if( isset( $_FILES["lay6"] ) )
{
    echo "<h2>".$_FILES["lay6"]["name"]."</h2>";
    echo '
        <a download="'.$filename.'" href="'.$filename.'" target="_blank"><img class="img-thumbnail img-fluid"
            src="'.$filename.'"
            style="width: 100%; max-width: 800px;"
        /></a><br /><br />';
}
?>
        </div>
    </body>
</html>

