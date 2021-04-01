<?php
use yii\helpers\Url;
use yii\helpers\Html;
use bin\admin\models\Album;
use bin\admin\models\Photo;
use kilyakus\helper\media\Image;
use kilyakus\button\Button;
use kilyakus\widget\redactor\Redactor;
use kilyakus\package\translate\widgets\TranslateForm;

use bin\user\models\User;

use kilyakus\web\widgets as Widget;

use app\assets\ScrollbarAsset;

ScrollbarAsset::register($this);

$modelClass = get_class($photo);

$dropDownList = '<select class="form-control photo-album" name="album">';
$dropDownList .= Html::tag('option', Yii::t('kilyakus/widget/gui', 'ALBUM_SELECT') . ' ...');
if(count($albums)){
	foreach ($albums as $album) {
		$options = ['value' => $album->primaryKey];
		if($photo->album_id == $album->primaryKey){
			$options['selected'] = 'selected';
		}
		$dropDownList .= Html::tag('option', $album->title, $options);
	}
}
$dropDownList .= Html::tag('option', Yii::t('kilyakus/widget/gui', 'ADD') . '...', ['value' => 'album-create']);
$dropDownList .= '</select>';

$modelName = @($photo ? (new \ReflectionClass($photo))->getShortName() : null);

echo Html::beginTag('div', ['class' => 'gui-item', 'data-id' => ($photo ? $photo->primaryKey : '{{photo_id}}')]);
	echo Html::beginTag('div', ['class' => 'gui-spacing ' . ($photo ? $modelClass::status($photo) : '{{photo_status}}')]);
		echo Html::beginTag('div', ['class' => 'gui-thumb']);
			echo Html::beginTag('div', ['class' => 'gui-cutter-container']);
				echo Html::beginTag('label', ['class' => 'kt-checkbox kt-checkbox--solid kt-checkbox--light position-absolute', 'style' => 'z-index:1;top:10px;left:10px;']);
					echo Html::checkbox($modelName . '[]', null, ['class' => 'gui-selectable', 'value' => ($photo ? $photo->primaryKey : '{{photo_id}}')]);
					echo Html::tag('span');
				echo Html::endTag('label');
				if($photo){
					echo Widget\Cutter::widget([
						'model' => $photo,
						'attribute' => 'image',
						'thumbWidth' => Photo::PHOTO_THUMB_WIDTH,
						'thumbHeight' => Photo::PHOTO_THUMB_HEIGHT,
						'imageContainer' => [
							'class' => 'plugin-box',
							'href' => !is_null($photo->video) ? $photo->video : $photo->image,
							'data-caption' => '<strong>' . $photo->title . '</strong><p>' . $photo->description . '</p>',
							'data-pjax' => '0'
						],
						'title' => $photo->title,
						'description' => $photo->description,
						'buttonDeleteSrc' => Url::to(['/admin/photos/delete/' . $photo->primaryKey])
					]);
				}else{
					echo Html::beginTag('div', ['class' => 'preview-container']);
						echo Html::beginTag('div', ['class' => 'controls-container']);
							echo Html::checkbox(null, null, ['class' => 'controls-toggler']); 
							echo Html::beginTag('label', ['for' => 'controls-toggler']);
							echo Html::tag('i', null, ['class' => 'fa fa-cog']);
							echo Html::endTag('label');
							
							echo Html::beginTag('ul');

								echo Html::beginTag('li', ['class' => 'controls-item']);
								echo Html::a(Html::tag('i', null, ['class' => 'fa fa-upload']), Url::to(['/admin/photos/change/{{photo_id}}']), ['class' => 'change-image-button', 'data-toggle' => 'kt-tooltip', 'data-placement' => 'left', 'data-original-title' => Yii::t('kilyakus/widget/gui', 'Change image')]);
								echo Html::input('file', 'Photo[image]', null, ['class' => 'change-image-input hidden']);
								echo Html::endTag('li');

								echo '<li class="controls-item">\
									<a href="' . Url::to(['/admin/photos/delete/{{photo_id}}']) . '" class="delete-photo" data-toggle="kt-tooltip" data-placement="left" data-original-title="' . Yii::t('kilyakus/widget/gui', 'Delete item') . '">\
										<i class="fa fa-trash"></i>\
									</a>\
								</li>';
							echo Html::endTag('ul');
						echo Html::endTag('div');

					echo Html::a('<img class="img-rounded" id="photo-{{photo_id}}" src="{{photo_thumb}}">', '{{photo_image}}', ['class' => 'plugin-box', 'title' => '{{photo_title}}', 'data-fancybox' => 'photos-{{model_id}}', 'data-pjax' => '0']);
					echo Html::endTag('div');
				}
				echo Html::beginTag('div', ['class' => 'gui-cutter-details']);
					echo Html::beginTag('div', ['class' => 'gui-details-info', 'style' => '--preview-image:url(\'' . ($photo ? Image::thumb($photo->image, Photo::PHOTO_THUMB_WIDTH, Photo::PHOTO_THUMB_HEIGHT) : '{{photo_thumb}}') . '\');']);
						echo Html::beginTag('div', ['class' => 'mCustomScrollbar', 'data-mcs-theme' => 'light-1']);
							echo Html::tag('strong', Yii::t('kilyakus/widget/gui', 'ALBUM') . ': ' . ($photo->album_id ? $photo->album->title : Yii::t('kilyakus/widget/gui', 'EMPTY'))) . '<br>';
							echo Html::tag('p', 
								Yii::t('kilyakus/widget/gui', 'TITLE') . ': ' . ($photo->title ? $photo->title : Yii::t('kilyakus/widget/gui', 'EMPTY')) . '<br>' .
								Yii::t('kilyakus/widget/gui', 'DESCRIPTION') . ': ' . ($photo->description ? $photo->description : Yii::t('kilyakus/widget/gui', 'EMPTY'))
							);
							echo Html::tag('span', Yii::t('kilyakus/widget/gui', 'AUTHOR') . ': ' .
								(
									$photo ? (
										$photo->author_src ? 
											Html::a($photo->author, $photo->author_src, ['class' => 'text-white', 'target' => '_blank'])
										: ($photo->author_id ? 
											Html::a($photo->author, Url::to(['/user/@' . User::findOne($photo->author_id)->username]), ['class' => 'text-white', 'target' => '_blank'])
											: ($photo->author ? $photo->author : Yii::t('kilyakus/widget/gui', 'EMPTY'))
										)
									) : '{{photo_author}}'
								)
							);
						echo Html::endTag('div');
					echo Html::endTag('div');
					echo Button::widget([
						'type' => Button::TYPE_TRANSPARENT,
						'size' => Button::SIZE_SMALL,
						'title' => Yii::t('kilyakus/widget/gui', 'DETAILS'),
						'icon' => 'fa fa-info-circle',
						'block' => true,
						'options' => [
							'class' => 'photo-info-panel',

						],
					]);
				echo Html::endTag('div');
			echo Html::endTag('div');
			echo Html::radio('primary', $photo->main == 1 ? true : false, ['class' => 'photo-primary',
				'label' => Yii::t('kilyakus/widget/gui', 'PRIMARY'),
				'data-confirm' => Yii::t('kilyakus/widget/gui', 'PRIMARY'),
				'data-href' => Url::to(['/admin/photos/description/' . ($photo ? $photo->primaryKey : '{{photo_id}}')]),
			]);
		echo Html::endTag('div');

			
		echo Html::beginTag('div', ['class' => 'gui-info']);

			echo Html::beginTag('div', ['class' => 'gui-header']);
				echo Html::tag('strong', Yii::t('kilyakus/widget/gui', 'DETAILS'));
				echo Html::tag('buton', '×', ['class' => 'close', 'data-toggle' => 'close']);
			echo Html::endTag('div');

			echo Html::beginTag('div', ['class' => 'gui-body']);

				$dropDownList;

				// ($photo ? 
				// 	TranslateForm::widget(['model' => $photo, 'attribute' => 'title']);
				// 	TranslateForm::widget(['model' => $photo, 'attribute' => 'description'])
				// :
					echo Html::input('text', null, ($photo ? $photo->title : '{{photo_title}}'), ['class' => 'form-control photo-title', 'placeholder' => Yii::t('kilyakus/widget/gui','TITLE')]);
					echo Html::textarea(null, ($photo ? $photo->description : '{{photo_description}}'), ['class' => 'form-control photo-description', 'placeholder' => Yii::t('kilyakus/widget/gui','DESCRIPTION')]);
				// );

			echo Html::input('text', null, ($photo ? $photo->author : '{{photo_author}}'), ['class' => 'form-control photo-author', 'placeholder' => Yii::t('kilyakus/widget/gui', 'AUTHOR')]);
			echo Html::input('url', null, 
				(
					$photo ?
					$photo->author_src :
					'{{photo_author_src}}'
				),
				['class' => 'form-control photo-author-src', 'placeholder' => Yii::t('kilyakus/widget/gui', 'AUTHOR_SRC')]
			);

			// echo Html::radio('primary', $photo->main == 1 ? true : false, ['class' => 'photo-primary',
			// 	'label' => Yii::t('kilyakus/widget/gui', 'PRIMARY')
			// ]);

			echo Html::endTag('div');
			echo Html::beginTag('div', ['class' => 'gui-footer']);
				echo Button::widget([
					'type' => Button::TYPE_PRIMARY,
					'size' => Button::SIZE_SMALL,
					'title' => Yii::t('kilyakus/widget/gui', 'SAVE'),
					'icon' => 'fa fa-compact-disc',
					'block' => true,
					'options' => [
						'class' => 'disabled save-photo-description',

					],
					'url' => Url::to(['/admin/photos/description/' . ($photo ? $photo->primaryKey : '{{photo_id}}')])
				]);
			echo Html::endTag('div');
		echo Html::endTag('div');
	echo Html::endTag('div');
echo Html::endTag('div');