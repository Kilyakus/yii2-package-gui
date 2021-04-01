<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use kilyakus\helper\media\Image;
use bin\admin\models\Album;
use bin\admin\models\Photo;
use kilyakus\widget\modal\Modal;
use kilyakus\button\Button;
use kartik\sortinput\SortableInput;
use kilyakus\widget\redactor\Redactor;
use kilyakus\web\widgets as Widget;

$photoTemplate = $this->render('photoTemplate');

$albumTemplate = "<span>{{album_title}}</span>\
<div class=\"pull-right\">" .
    // "<a class=\"\" data-pjax=\"0\" href=\"" . Url::to(['/admin/albums/up/{{album_id}}'] + $linkParams) . "\">" . Html::tag('i', null, ['class' => 'fa fa-arrow-up']) . "</a>\
    // <a class=\"\" data-pjax=\"0\" href=\"" . Url::to(['/admin/albums/down/{{album_id}}'] + $linkParams) . "\">" . Html::tag('i', null, ['class' => 'fa fa-arrow-down']) . "</a>" .
    "<a class=\"delete-album\" data-confirm=\"" . Yii::t('kilyakus/cutter/cutter', 'REMOVE') . "\" href=\"" . Url::to(['/admin/albums/delete/{{album_id}}']) . "\">" . Html::tag('i', null, ['class' => 'fa fa-times text-info']) . "</a>\
</div>";

$this->registerJs("
var guiPhotoUploadUrl = '" . Url::to([$uploadPhotoUrl] + $linkParams) . "';
var guiPhotoDeleteUrl = '" . Url::to(['/admin/photos/delete']) . "/';
var guiVideoUploadUrl = '" . Url::to([$uploadVideoUrl] + $linkParams) . "';
var photoTemplate = '<li data-key=\"{{photo_id}}\" draggable=\"true\" role=\"option\" aria-grabbed=\"false\">{$photoTemplate}</li>';
var albumTemplate = '<li data-key=\"{{album_id}}\" class=\"{{album_status}}\" draggable=\"true\" role=\"option\" aria-grabbed=\"false\">{$albumTemplate}</li>';
", \yii\web\View::POS_HEAD);
// $photoTemplate = str_replace('>\\', '>', $photoTemplate);
$albumTemplate = str_replace('>\\', '>', $albumTemplate);

$this->registerCss("
.gui-cutter-container img {width:100%;height:" . Photo::PHOTO_THUMB_HEIGHT . "px;}
.gui-widget .gui-sortable > li > div.gui-item .gui-thumb .gui-cutter-details .gui-details-info:before {background-size:100% " . Photo::PHOTO_THUMB_HEIGHT . "px;}
");

$albumsTemplate = $contentAlbum = [];
if(count($albums)){
	foreach ($albums as $album) {
		$albumsTemplate[] = $album->album_id;
		$contentAlbum[$album->album_id] = [
			'content' => str_replace(
				['{{album_status}}', '{{album_id}}', '{{album_title}}', '{{album_description}}'],
				[Album::status($album), $album->primaryKey, $album->title, $album->description],
				$albumTemplate
			)
		];
	}
	$albumsTemplate = implode(',',$albumsTemplate);
}

$values = $content = [];
if(count($photos)){
	foreach ($photos as $photo) {
		$values[] = $photo->photo_id;
		$content[$photo->photo_id] = [
			// 'content' => str_replace(
			// 	['{{photo_status}}', '{{photo_id}}', '{{photo_thumb}}', '{{photo_image}}', '{{photo_title}}', '{{photo_description}}'],
			// 	[Photo::status($photo), $photo->primaryKey, Image::thumb($photo->image, 270, 180), $photo->image, $photo->title, $photo->description],
			// 	$photoTemplate
			// )
			'content' => $this->render('photoTemplate', ['photo' => $photo, 'albums' => $albums]),
		];
	}
	$values = implode(',',$values);
}
?>

<?= Html::beginTag('div', ['class' => 'gui-widget']); ?>

	<?= Html::beginTag('div', ['class' => 'btn-group', 'style' => 'max-width:100%;']); ?>
	<?php Modal::begin([
		'id' => $id . '-modal-photos',
		'toggleButton' => [
			'type' => Button::TYPE_SECONDARY,
			'title' => Yii::t('kilyakus/widget/gui', 'UPLOAD_IMAGES'),
			'icon' => 'fa fa-paperclip',
			'options' => [
				'type' => 'button',
				'class' => 'photo-modal',
			],
		],
		'header' => '<h2><i class="fa fa-upload"></i> Прикрепить</h2><p>Загрузите файлы с компьютера или вставьте ссылку</p>',
		'footer' => '<div class="row"><div class="col-xs-12 col-md-6 col-md-offset-6">' . Button::widget([
			'type' => Button::TYPE_SECONDARY,
			'size' => Button::SIZE_LARGE,
			'title' => Yii::t('kilyakus/widget/gui', 'CLOSE'),
			'block' => true,
			'options' => [
				'data-dismiss' => 'ui-dialog',
				'class' => 'ui-dialog-titlebar-close'
			]
		]) . '</div></div>',
		'pluginOptions' => [
			'appendTo' => '.gui-widget',
			'width' => 500,
		],
		'extendOptions' => [
			'titlebar' => false,
			'maximizable' => false,
			'minimizable' => false,
		]
	]) ?>

		<div class="form-group">
			<div class="row">
				<div class="col-lg-6">
					<label class="kt-option">
						<span class="kt-option__control">
							<span class="kt-radio">
								<input type="radio" name="Photo[author]" value="0" checked="checked">
								<span></span>
							</span>
						</span>
						<span class="kt-option__label">
							<span class="kt-option__head">
								<span class="kt-option__title"><?= Yii::t('kilyakus/widget/gui', 'UNKNOWN_AUTHOR') ?></span>
							</span>
							<span class="kt-option__body"><?= Yii::t('kilyakus/widget/gui', 'UNKNOWN_AUTHOR_DESC') ?></span>
						</span>
					</label>
				</div>
				<div class="col-lg-6">
					<label class="kt-option">
						<span class="kt-option__control">
							<span class="kt-radio">
								<input type="radio" name="Photo[author]" value="1">
								<span></span>
							</span>
						</span>
						<span class="kt-option__label">
							<span class="kt-option__head">
								<span class="kt-option__title"><?= Yii::t('kilyakus/widget/gui', 'IM_AUTHOR') ?></span>
							</span>
							<span class="kt-option__body"><?= Yii::t('kilyakus/widget/gui', 'IM_AUTHOR_DESC') ?></span>
						</span>
					</label>
				</div>
			</div>
			<br>
			<label class="kt-option">
				<span class="kt-option__control">
					<span class="kt-radio">
						<input type="radio" name="Photo[author]" value="2">
						<span></span>
					</span>
				</span>
				<span class="kt-option__label">
					<span class="kt-option__head">
						<?= Html::textInput('Photo[author]',null,['class' => 'gui-author form-control', 'placeholder' => Yii::t('kilyakus/widget/gui', 'AUTHOR')]); ?>
					</span>
					<span class="kt-option__body"><?= Html::input('url', 'Photo[author_src]', null, ['class' => 'gui-author-src form-control', 'placeholder' => Yii::t('kilyakus/widget/gui', 'AUTHOR_SRC')]); ?></span>
				</span>
			</label>
		</div>

		<div class="form-group">
		<?= Html::beginTag('label', ['class' => 'kt-checkbox kt-checkbox--solid']) . 
			Html::checkbox(null, 1, ['disabled' => true]) .
			Html::tag('span') . 'Загружая изображения и видео на сервер, Вы автоматически соглашаетесь с правилами сообщества.' .
		Html::endTag('label') ?>
		</div>

		<?php /* Html::dropDownList(null, null, ArrayHelper::map($albums, 'title', 'album_id'), ['class' => 'form-control']); */ ?>

		<?= Button::widget([
			'type' => Button::TYPE_SECONDARY,
			'encodeLabel' => false,
			'title' => Yii::t('kilyakus/widget/gui', 'DOWNLOAD'),
			'block' => true,
			'options' => [
				'class' => 'gui-upload-button text-uppercase'
			]
		]) ?>
		<div class="gui-strip">
			<span><?= Yii::t('kilyakus/widget/gui', 'OR') ?></span>
		</div>
		<div class="gui-link">
			<?= Html::textInput('pastelink',null,['class' => 'gui-link-src', 'placeholder' => Yii::t('kilyakus/widget/gui', 'PASTE_LINK')]); ?>
			<?= Html::button('<i class="fa fa-times"></i>', ['class' => 'gui-link-discard']) ?>
			<?= Html::button('<i class="fa fa-check"></i>', [
				'class' => 'gui-link-send',
				'data-boundary' => 'window',
				'data-toggle' => 'tooltip',
				'data-html' => 'true',
				'data-placement' => 'top',
				'data-trigger' => 'click',
				'data-original-title' => '',
			]) ?>
		</div>

		<div class="gui-uploading-text">
			<small><?= Yii::t('kilyakus/widget/gui', 'UPLOADING') ?><span></span></small>
		</div>
		
	<?php Modal::end() ?>

	<?php Modal::begin([
		'id' => $id . '-modal-albums',
		'toggleButton' => [
			'type' => Button::TYPE_SECONDARY,
			'title' => Yii::t('kilyakus/widget/gui', 'ALBUM_MANAGEMENT'),
			'icon' => 'fa fa-image',
			'options' => [
				'type' => 'button',
				'class' => 'album-modal hidden',
			],
		],
		'header' => '<strong><i class="fa fa-cog"></i> ' . Yii::t('kilyakus/widget/gui', 'ALBUM_MANAGEMENT') . '</strong>',
		'footer' => '<div class="row"><div class="col-xs-12 col-md-6 col-md-offset-6">' . Button::widget([
			'type' => Button::TYPE_SECONDARY,
			'size' => Button::SIZE_LARGE,
			'title' => Yii::t('kilyakus/widget/gui', 'CLOSE'),
			'block' => true,
			'options' => [
				'data-dismiss' => 'ui-dialog',
				'class' => 'ui-dialog-titlebar-close'
			]
		]) . '</div></div>',
		'pluginOptions' => [
			'appendTo' => '.gui-widget',
			'width' => 500,
		],
		'extendOptions' => [
			'titlebar' => false,
			'maximizable' => false,
			'minimizable' => false,
		]
	]) ?>

		<?= Html::beginTag('div', ['class' => 'row']); ?>
			<?= Html::beginTag('div', ['class' => 'col-8']); ?>
				<?= Html::textInput('Album[title]', null, ['class' => 'form-control album-title', 'placeholder' => Yii::t('kilyakus/widget/gui', 'TITLE')]); ?>
			<?= Html::endTag('div'); ?>
			<?= Html::beginTag('div', ['class' => 'col-4']); ?>
				<?= Button::widget([
					'type' => Button::TYPE_SECONDARY,
					'encodeLabel' => false,
					'title' => Yii::t('kilyakus/widget/gui', 'ADD'),
					'block' => true,
					'options' => [
						'class' => 'gui-create-album text-uppercase',
						'data-href' => Url::to([$createUrl] + $linkParams),
					]
				]) ?>
			<?= Html::endTag('div'); ?>
		<?= Html::endTag('div'); ?>
		<br>
		<?= Html::textarea('Album[description]', null, ['class' => 'form-control album-description', 'placeholder' => Yii::t('kilyakus/widget/gui', 'DESCRIPTION')]); ?>

		<div class="gui-albums<?= count($albums) ? '' : ' hidden' ?>">

			<div class="gui-strip">
				<span><?= Yii::t('kilyakus/widget/gui', 'ALBUM_LIST') ?></span>
			</div>

			<?= SortableInput::widget([
				'sortableOptions' => [
					'type' => 'album-list',
					'options' => [
						// 'class' => 'gui-sortable',
					],
				],
				'name'=> 'albums',
				'value' => $albumsTemplate,
				'items' => $contentAlbum,
				'hideInput' => true,
				'options' => [
					'class' => 'form-control',
					'readonly' => true,
					'id' => 'sortAlbums'
				],
			]); ?>
		</div>
		
	<?php Modal::end() ?>
	<?= Html::endTag('div'); ?>

	<div class="btn btn-secondary">
		<?= Html::beginTag('label', ['class' => 'kt-checkbox kt-checkbox--solid', 'style' => 'margin:0;']) . 
			Html::checkbox('Photo[]', null, ['class' => 'gui-selectall']) .
			Html::tag('span') . Yii::t('kilyakus/widget/gui', 'SELECT_ALL') .
		Html::endTag('label'); ?>
	</div>

	<?= Widget\Button::widget([
		'type' => Button::TYPE_SECONDARY,
		'title' => Yii::t('kilyakus/widget/gui', 'DELETE_MARKED'),
		'icon' => 'fa fa-times',
		'outline' => true,
		'options' => [
			'class' => 'gui-delete-selectable disabled',
			'data-confirm' => Yii::t('kilyakus/widget/gui', 'DELETE_MARKED')
		],
	]); ?>

	<div class="gui-toggle">
		<input type="radio" name="sizeBy" value="photo-grid" id="radioGrid" checked="checked" />
		<label for="radioGrid"><i class="fa fa-th-large"></i></label>
		<input type="radio" name="sizeBy" value="photo-list" id="radioList" />
		<label for="radioList"><i class="fa fa-th-list"></i></label>
	</div>
	<hr>
	<?= SortableInput::widget([
		'sortableOptions' => [
			'type' => 'photo-grid',
			'options' => [
				'class' => 'gui-sortable',
				'style' => count($photos) ?: 'display:none;'
			],
		],
		'name'=> 'photos',
		'value' => $values,
		'items' => $content,
		'hideInput' => true,
		'options' => [
			'class' => 'form-control',
			'readonly' => true,
			'id' => 'sortImage'
		],
	]); ?>

	<p class="empty" style="display: <?= count($photos) ? 'none' : 'block' ?>;"><?= Yii::t('kilyakus/widget/gui', 'NO_PHOTO') ?>.</p>

	<?= Html::beginForm(Url::to([$uploadPhotoUrl] + $linkParams), 'post', ['enctype' => 'multipart/form-data']) ?>
	<?= Html::textInput('', null, [
		'id' => 'photo-link',
		'class' => 'hidden',
	])
	?>
	<?= Html::fileInput('', null, [
		'id' => 'photo-file',
		'class' => 'hidden',
		'multiple' => 'multiple',
	])
	?>
	<?php Html::endForm() ?>

<?= Html::endTag('div'); ?>

<?php $this->registerJs("
$('.gui-toggle input[type=radio]').on('change',function(){
	$('.gui-sortable').removeClass('photo-grid photo-list');
	$('.gui-sortable').addClass($(this).val())
})
", $this::POS_END); ?>