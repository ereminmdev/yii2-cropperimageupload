<?php

namespace ereminmdev\yii2\cropperimageupload;

use yii\web\AssetBundle;

class CropperAsset extends AssetBundle
{
    public $sourcePath = '@npm/cropperjs/dist';

    public $js = [
        YII_DEBUG ? 'cropper.js' : 'cropper.min.js',
    ];

    public $css = [
        YII_DEBUG ? 'cropper.css' : 'cropper.min.css',
    ];
}
