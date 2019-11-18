<?php
namespace kilyakus\package\gui\behaviors;

use Yii;
use yii\db\ActiveRecord;

class GuiBehavior extends \yii\base\Behavior
{
    public $root;

    public $className;

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
        if(Yii::$app->request->post('Photo')){

            $className = $this->className;

            $photos = $className::find()->where([
                'and',
                ['class' => $this->owner::className()],
                [
                    'or',
                    ['item_id' => '0'],
                    ['status' => $className::STATUS_UPLOADED],
                ]
            ])->all();

            foreach ($photos as $photo) {
                $photo->item_id = $this->owner->primaryKey;
                if($root){
                    $photo->status = $className::STATUS_ON;
                }else{
                    $photo->status = $className::STATUS_OFF;
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