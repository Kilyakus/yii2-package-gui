<?php
namespace kilyakus\package\gui\actions;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\Url;
use kilyakus\action\BaseAction as Action;
use kilyakus\helper\media\Image;
use bin\user\models\User;

class UploadAction extends Action
{
	public $model;

	public $ownerAttribute = 'image';

	public $basePath = 'photos';

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

			if($author = $post['author']){

				if($author === 0){
					$model->author_id = null;
					$model->author = null;
				}elseif($author === 1){
					// $model->author_id = Yii::$app->user->identity->id;
					$model->author = Yii::$app->user->identity->name;
				}elseif(is_string($author)){
					$model->author_id = null;
					$model->author = $post['author'];
				}else{
					$model->author = Yii::$app->user->identity->name;
				}
			}

			if($src = $post['author_src'])
			{
				$model->author_src = $post['author_src'];
			}

			if($model->image && $model->validate(['image']))
			{
				if(!is_string($model->image)){
					$model->image = Image::upload($model->image, $this->basePath . '/' . Yii::$app->user->identity->id, $modelClass::PHOTO_MAX_WIDTH);
				}

				if($model->image){
					if($model->save()){
						$owner = $class::findOne($model->item_id);
						if(($owner = $class::findOne($model->item_id)) && empty($owner->{$this->ownerAttribute}))
						{
							$owner->{$this->ownerAttribute} = $model->image;
							$owner->update();
						}

						$success = [
							'message' => Yii::t('easyii', 'Photo uploaded'),
							'model' => [
								'id' => $item_id,
							],
							'record' => [
								'id' => $model->primaryKey,
								'album' => $model->album_id,
								'image' => $model->image,
								'thumb' => Image::thumb($model->image, $modelClass::PHOTO_THUMB_WIDTH, $modelClass::PHOTO_THUMB_HEIGHT),
								'title' => '',
								'description' => '',
								'author' => $model->author ? $model->author : '',
								'author_src' => $model->author_src ? $model->author_src : '',
								'status' => $modelClass::status($model),
							]
						];
					}
					else{
						@unlink(Yii::getAlias('@webroot') . str_replace(Url::base(true), '', $model->image));
						$this->error = Yii::t('easyii', 'Create error. {0}', Yii::t('easyii', 'Check the sent data for errors.'));
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
