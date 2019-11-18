<?php
namespace kilyakus\package\gui\actions;

use Yii;
use yii\web\UploadedFile;
use kilyakus\action\BaseAction as Action;
use kilyakus\imageprocessor\Image;

class ChangeAction extends Action
{
    public $model;

    public function run($id)
    {
        $success = null;

        if(!($modelClass = $this->model)){

            $this->error = Yii::t('easyii', 'Action property `model` is empty');

        }else{

            if(($model = $modelClass::findOne($id)))
            {
                $oldImage = $model->image;

                $model->image = UploadedFile::getInstance($model, 'image');

                if($model->image && $model->validate(['image'])){
                    $model->image = Image::upload($model->image, 'photos', $modelClass::PHOTO_MAX_WIDTH);
                    if($model->image){
                        if($model->save()){
                            @unlink(Yii::getAlias('@webroot').$oldImage);

                            $success = [
                                'message' => Yii::t('easyii', 'Photo uploaded'),
                                'photo' => [
                                    'image' => $model->image,
                                    'thumb' => Image::thumb($model->image, $modelClass::PHOTO_THUMB_WIDTH, $modelClass::PHOTO_THUMB_HEIGHT)
                                ]
                            ];
                        }
                        else{
                            @unlink(Yii::getAlias('@webroot').$model->image);

                            $this->error = Yii::t('easyii', 'Update error. {0}', $model->formatErrors());
                        }
                    }
                    else{
                        $this->error = Yii::t('easyii', 'File upload error. Check uploads folder for write permissions');
                    }
                }
                else{
                    $this->error = Yii::t('easyii', 'File is incorrect');
                }

            }
            else{
                $this->error =  Yii::t('easyii', 'Not found');
            }
        }

        return $this->formatResponse($success);
    }
}