<?php
namespace kilyakus\package\gui\actions;

use Yii;
use kilyakus\action\BaseAction as Action;

class DeleteAction extends Action
{
    public $model;

    public $onwerAttribute = 'image';

    public function run($id)
    {
        $modelClass = $this->model;

        if(($model = $modelClass::findOne($id)))
        {
            $className = $model->class;

            if(($owner = $className::findOne($model->item_id)) && isset($owner->{$this->onwerAttribute}))
            {
                if($owner->{$this->onwerAttribute} == $model->image)
                {
                    $owner->{$this->onwerAttribute} = null;
                    $owner->update();
                }
            }

            $model->delete();
        } else {
            $this->error = Yii::t('easyii', 'Not found');
        }
        return $this->formatResponse(Yii::t('easyii', 'Photo deleted'));
    }
}