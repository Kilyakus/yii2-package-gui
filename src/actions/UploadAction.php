<?php
namespace kilyakus\package\gui\actions;

use Yii;
use yii\web\UploadedFile;
use kilyakus\action\BaseAction as Action;
use kilyakus\imageprocessor\Image;

class UploadAction extends Action
{
    public $model;

    public function run($class, $item_id = null)
    {
        $success = null;

        if(!($modelClass = $this->model)){

            $this->error = Yii::t('easyii', 'Action property `model` is empty');

        }else{
            $model = new $modelClass;
            $model->class = $class;
            if($item_id){
                $model->item_id = $item_id;
            }
            $post = Yii::$app->request->post('Photo');

            if($file = $post['link']){

                $model->image = Image::copyImage($file, 'copy');

            }else{
            
                $model->image = UploadedFile::getInstance($model, 'image');

            }

            if($model->image && $model->validate(['image'])){

                if(!is_string($model->image)){
                    $model->image = Image::upload($model->image, 'photos', $modelClass::PHOTO_MAX_WIDTH);
                }

                if($model->image){
                    if($model->save()){
                        $success = [
                            'message' => Yii::t('easyii', 'Photo uploaded'),
                            'photo' => [
                                'id' => $model->primaryKey,
                                'image' => $model->image,
                                'thumb' => Image::thumb($model->image, $modelClass::PHOTO_THUMB_WIDTH, $modelClass::PHOTO_THUMB_HEIGHT),
                                'description' => '',
                                'status' => $modelClass::status($model),
                            ]
                        ];
                    }
                    else{
                        @unlink(Yii::getAlias('@webroot') . str_replace(Url::base(true), '', $model->image));
                        $this->error = Yii::t('easyii', 'Create error. {0}', $model->formatErrors());
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

        return $this->formatResponse($success);
    }
}