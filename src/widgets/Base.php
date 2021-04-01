<?php
namespace bin\admin\widgets\ModulePhotos;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use kilyakus\widget\fancybox\Fancybox;
use bin\admin\models\Album;
use bin\admin\models\Photo;

class ModulePhotos extends Widget
{
    public $model;

    public $sort = [
        'up' => '/admin/photos/up/',
        'down' => '/admin/photos/down/',
    ];

    public $uploadPhotoUrl = '/admin/photos/upload';
    public $uploadVideoUrl = '/admin/videos/upload';

    public $createUrl = '/admin/albums/create';

    public $photos;

    public $params = [];

    public $template = 'photos';

    public $photoTemplate;

    public function init()
    {
        parent::init();

        if (empty($this->model)) {
            // throw new InvalidConfigException('Required `model` param isn\'t set.');
        }

        if (empty($this->params)) {
            $this->params = [
                'class' => get_class($this->model),
                'item_id' => $this->model->primaryKey,
            ];
        }

        $this->registerTranslations();
    }

    public function run()
    {
        $albums = static::getAlbums();

        if(is_array($this->photos) || is_object($this->photos)){
            $photos = $this->photos;
        }else{
            $photos = static::getPhotos();
        }

        static::registerAssets();

        echo $this->render($this->template, [
            'id' => $this->id,
            'albums' => $albums,
            'photos' => $photos,
            'uploadPhotoUrl' => $this->uploadPhotoUrl,
            'uploadVideoUrl' => $this->uploadVideoUrl,
            'createUrl' => $this->createUrl,
            'linkParams' => $this->params,

        ]);
    }

    protected function getPhotos()
    {
        $searchModel  = \Yii::createObject(Photo::className());
        $dataProvider = $searchModel->search(\Yii::$app->request->get());
        $dataProvider->query->andFilterWhere([
            'and',
            ['class' => get_class($this->model)],
            [
                'or',
                ['is', 'item_id', new \yii\db\Expression('null')],
                ['item_id' => '0'],
                ['item_id' => $this->model->primaryKey],
            ],
            // ['is', 'field_instance', new \yii\db\Expression('null')],
        ]);
        if(isset($this->model->created_by)){
            if(!IS_MODER && $this->model->created_by != Yii::$app->user->identity->id){

                $dataProvider->query->andFilterWhere(['and',['created_by' => Yii::$app->user->identity->id],['status' => Photo::STATUS_UPLOADED]]);

            }
        }

        $dataProvider->query->orderBy(['order_num' => SORT_DESC]);

        $dataProvider->pagination = false;

        return $dataProvider->getModels();
    }

    protected function getAlbums()
    {
        $searchModel  = \Yii::createObject(Album::className());
        $dataProvider = $searchModel->search(\Yii::$app->request->get());
        $dataProvider->query->andFilterWhere([
            'and',
            ['class' => get_class($this->model)],
            [
                'or',
                ['is', 'item_id', new \yii\db\Expression('null')],
                ['item_id' => '0'],
                ['item_id' => $this->model->primaryKey]
            ]
        ]);

        if(isset($this->model->created_by)){
            if(!IS_MODER && $this->model->created_by != Yii::$app->user->identity->id){

                $dataProvider->query->andFilterWhere(['and',['created_by' => Yii::$app->user->identity->id],['status' => Album::STATUS_UPLOADED]]);

            }
        }

        $dataProvider->query->orderBy(['order_num' => SORT_DESC]);

        $dataProvider->pagination = false;

        return $dataProvider->getModels();
    }

    protected function registerAssets()
    {
        $view = $this->getView();
        ModulePhotosAsset::register($view);

        $options = http_build_query($this->params);

        $view->registerJs("
var dragging = null;
$('.sortable li').on('mousemove',function(){
    dragging = $(this).attr('data-key');
})
var vars = $('#sortImage').val().split(',');
$('#sortImage').on('change',function(){
    var items = this.value.split(','),o,n,count,url;
    for (var i = 0; i < vars.length; i++){if(vars[i] == dragging){o=i;}}
    for (var i = 0; i < items.length; i++){if(items[i] == dragging){n=i;}}
    count = o-n;
    if(count > 0){
        url = '" . $this->sort['up'] . "'+dragging+'?$options'+'&count='+count;
    }else{
        count = Math.abs(count);
        url = '" . $this->sort['down'] . "'+dragging+'?$options'+'&count='+count;
    }
    $.ajax({type:'post',url:url})
})
", $view::POS_END,'ModulePhotos');

        Fancybox::widget(['selector' => '.plugin-box','group' => 'photos-' . $this->model->primaryKey]);
    }

    public function registerTranslations()
    {
        Yii::$app->i18n->translations['kilyakus/cutter/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@vendor/kilyakus/yii2-widget-cutter/src/messages',
            'fileMap' => [
                'kilyakus/cutter/cutter' => 'cutter.php',
            ],
        ];

        Yii::$app->i18n->translations['kilyakus/widget/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en-US',
            'basePath' => '@bin/admin/widgets/ModulePhotos/messages',
            'fileMap' => [
                'kilyakus/widget/gui' => 'gui.php',
            ],
        ];
    }
}