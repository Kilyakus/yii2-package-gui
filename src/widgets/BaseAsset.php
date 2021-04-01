<?php
namespace bin\admin\widgets\ModulePhotos;

class ModulePhotosAsset extends \yii\web\AssetBundle
{
    public $depends = [
        'yii\web\JqueryAsset',
        'kilyakus\cutter\ControlsAsset',
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . '/assets';

        $this->js[] = 'js/photos.js';

        $this->css[] = 'css/photos.css';

        parent::init();
    }
}