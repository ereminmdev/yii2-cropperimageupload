<?php

namespace ereminmdev\yii2\cropperimageupload;

use yii\web\AssetBundle;

class CropperImageUploadAsset extends AssetBundle
{
    public $sourcePath = '@vendor/ereminmdev/yii2-cropperimageupload/assets';

    public $js = [
        'cropperImageUpload.js',
    ];

    public $css = [
        'cropperImageUpload.css',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        'ereminmdev\yii2\cropperimageupload\CropperAsset',
    ];
}
