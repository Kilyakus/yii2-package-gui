<?php
namespace kilyakus\package\gui\actions;

use Yii;
use kilyakus\action\BaseAction as Action;

class DeleteAction extends Action
{
    public $model;

    public function run($id)
    {
        $className = $this->model;

        if(($model = $className::findOne($id))){
            $model->delete();
        } else {
            $this->error = Yii::t('easyii', 'Not found');
        }
        return $this->formatResponse(Yii::t('easyii', 'Photo deleted'));
    }
}