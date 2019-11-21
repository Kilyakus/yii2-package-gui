<?php
namespace kilyakus\package\gui\behaviors;

use Yii;
use yii\db\ActiveRecord;

class GuiBehavior extends \yii\base\Behavior
{
    public $model;

    public $isRoot;

    public $identity;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    public function afterInsert()
    {
        self::afterUpload();
    }

    public function afterUpdate()
    {
        self::afterUpload();
    }

    public function afterUpload()
    {
        $modelClass = $this->model;

        $searchModel  = \Yii::createObject($modelClass);
        $dataProvider = $searchModel->search(\Yii::$app->request->get());

        $query = [
            'and',
            ['class' => $this->owner::className()],
            [
                'or',
                ['is', 'item_id', new \yii\db\Expression('null')],
                ['item_id' => '0'],
                ['item_id' => $this->owner->primaryKey],
                ['status' => $modelClass::STATUS_UPLOADED],
            ]
        ];

        $dataProvider->query->andFilterWhere($query);

        if($this->isRoot && $this->owner->primaryKey){
            $dataProvider->query->andFilterWhere(['in', 'status', [$modelClass::STATUS_OFF,$modelClass::STATUS_UPLOADED]]);
        }

        if(!$this->isRoot && $this->identity){
            $dataProvider->query->andFilterWhere(['created_by' => $this->identity]);
        }

        $dataProvider->pagination = false;

        if(Yii::$app->request->post((new \ReflectionClass($modelClass))->getShortName()) || $dataProvider->query->count()){

            foreach ($dataProvider->getModels() as $photo) {
                $photo->item_id = $this->owner->primaryKey;
                if($this->isRoot){
                    $photo->status = $modelClass::STATUS_ON;
                }else{
                    $photo->status = $modelClass::STATUS_OFF;
                }
                
                $photo->update();
            }

        }
    }

    public function afterDelete()
    {
        $className = $this->className;

        $className::deleteAll(['class' => $this->owner::className(), 'item_id' => $this->owner->primaryKey]);
    }
}