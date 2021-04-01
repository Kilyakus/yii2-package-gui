$(function(){
	var container = $('.gui-widget');
	var photosBody = $('.gui-sortable');
	var uploadButton = $('.gui-upload-button');
	var uploadSrc = $('.gui-link-src');
	var uploadPreview = $('.gui-link-send');
	var uploadDiscard = $('.gui-link-discard');
	var uploadingText = $('.gui-uploading-text');
	var uploadingTextInterval;
	var uploadPreloader = 'gui-preloader';

	var selectAllButton = $('.gui-selectall');
	var deleteButton = $('.gui-delete-selectable');

	var reUrlYoutube = /https?:\/\/(?:[0-9A-Z-]+\.)?(?:youtu\.be\/|youtube\.com\S*[^\w\-\s])([\w\-]{11})(?=[^\w\-]|$)(?![?=&+%\w.-]*(?:['"][^<>]*>|<\/a>))[?=&+%\w.-]*/ig;
	var reUrlVimeo = /(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([‌​0-9]{6,11})[?]?.*/;

	if(location.hash){
		$('img'+location.hash).closest('li[draggable]').addClass('info');
	}

	uploadButton.on('click', function(){
		$('#photo-file').trigger('click');
	});

	toastr.options = {
		'closeButton': true,
		'debug': false,
		'newestOnTop': false,
		'progressBar': true,
		'positionClass': 'toast-bottom-right',
		'preventDuplicates': false,
		'onclick': null,
		'showDuration': '300',
		'hideDuration': '1000',
		'timeOut': '5000',
		'extendedTimeOut': '5000',
		'showEasing': 'swing',
		'hideEasing': 'linear',
		'showMethod': 'fadeIn',
		'hideMethod': 'fadeOut'
	}

	$('#photo-file').on('change', function(){
		var $this = $(this);

		uploadButton.addClass('disabled');

		uploadingTextInterval = setInterval(dotsAnimation, 300);

		var uploaded = 0;
		$.each($this.prop('files'), function(i, file){
			if(/^image\/(jpeg|png|gif|webp)$/.test(file.type))
			{
				sendData($this, file, guiPhotoUploadUrl, uploaded);
			}else if(file.type == 'video/mp4'){
				sendData($this, file, guiVideoUploadUrl, uploaded);
			}
			uploaded++;
		});
	});

	function getCheckedRadioValue(name) {
		var elements = document.getElementsByName(name);

		for (var i=0, len=elements.length; i<len; ++i)
			if (elements[i].checked) return elements[i].value;
	}


	function sendData(el, file, guiUploadUrl, uploaded){
		var $this = el;
		var $uploaded = uploaded;

		if(file){
			$(uploadPreview.parents('.ui-widget-body')).addClass(uploadPreloader);

			var formData = new FormData();

			if(guiUploadUrl == guiPhotoUploadUrl)
			{
				var format = typeof(file) == 'string' ? 'link' : 'image';
				var model = 'Photo';
			}else{
				var format = typeof(file) == 'string' ? 'link' : 'video';
				var model = 'Photo';
			}

			formData.append(model+'['+format+']', file);

			var author = getCheckedRadioValue(model+'[author]');

			if($(container.find('.gui-author')).val() && author == 2){
				author = $(container.find('.gui-author')).val();
				formData.append('Photo[author_src]', $(container.find('.gui-author-src')).val());
			}

			formData.append(model+'[author]', author);

			$.ajax({
				url: guiUploadUrl, // $this.closest('form').attr('action'),
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				data: formData,
				type: 'post',
				success: function(response){
					if(response.result === 'success'){
						var html = $(photoTemplate
							.replace(/\{\{model_id\}\}/g, response.model.id)
							.replace(/\{\{photo_id\}\}/g, response.record.id)
							.replace(/\{\{photo_album\}\}/g, response.record.album)
							.replace(/\{\{photo_thumb\}\}/g, response.record.thumb)
							.replace(/\{\{photo_image\}\}/g, response.record.image)
							.replace(/\{\{photo_title\}\}/g, '')
							.replace(/\{\{photo_description\}\}/g, '')
							.replace(/\{\{photo_author\}\}/g, response.record.author)
							.replace(/\{\{photo_author_src\}\}/g, response.record.author_src)
							.replace(/\{\{photo_status\}\}/g, response.record.status))
							.hide();
						
						if(!$('#photo-table').length){
							var prevId = $('li[data-key='+( response.record.id - 1 )+']', photosBody);
						}else{
							var prevId = $('tr[data-id='+( response.record.id - 1 )+']', photosBody);
						}
						// console.log(prevId)
						if(prevId.get(0)){
							prevId.before(html);
						} else {
							photosBody.prepend(html);
						}

						checkEmpty();
						html.fadeIn();
					} else {
						alert(response.error);
					}

					if($uploaded === 0 || $this.prop('files') && ($uploaded >= $this.prop('files').length))
					{
						uploadButton.removeClass('disabled');
						$(uploadButton.parents('.ui-widget-body')).removeClass(uploadPreloader);
						clearInterval(uploadingTextInterval);

						$('gui-details-info > .mCustomScrollbar').mCustomScrollbar();
					}
				}
			});
		}
	}

	uploadSrc.on('input propertychange', function(){

		var $val = $(this).val();

		if ($val.match(reUrlYoutube)){
			$val = $val.replace(reUrlYoutube, 'https://www.youtube.com/embed/$1');
			$(this).val($val);
			checkVideoSrc($val);
		}
		else if ($val.match(reUrlVimeo)){
			$val = $val.replace(reUrlVimeo, 'https://player.vimeo.com/video/$5');
			$(this).val($val);
			checkVideoSrc($val);
		}
		else{
			checkImgSrc($val);
		}
	});

	uploadDiscard.on('click',function(){
		uploadSrc.val(null);
		resetSrc();
	})

	uploadPreview.on('click', function(){

		var $this = $(this), $val = uploadSrc.val();

		uploadingTextInterval = setInterval(dotsAnimation, 300);

		if ($val.match(reUrlYoutube) || $val.match(reUrlVimeo)){
			var guiUploadUrl = guiVideoUploadUrl;
		}
		else{
			var guiUploadUrl = guiPhotoUploadUrl;
		}

		sendData($this, $val, guiUploadUrl, 0);

		uploadSrc.val(null);

		resetSrc();

	});

	const checkVideoSrc = src => {
		setTimeout(function(){
			$(uploadSrc.parent()).addClass('confirmed');
			uploadPreview.attr('data-original-title', '<div style="width:560px;height:315px;"><iframe width="560" height="315" src="' + src + '" frameborder="0"></iframe></div>');
			uploadPreview.hover();
			uploadPreview.tooltip('show');
		},0);
	}

	const checkImgSrc = src => {
		const img = new Image();
		img.onload = function () {
			setTimeout(function(){
				$(uploadSrc.parent()).addClass('confirmed');
				uploadPreview.attr('data-original-title', '<img width="170" src="' + src + '">');
				uploadPreview.hover();
				uploadPreview.tooltip('show');
			},0);
		}
		img.onerror = function () {
			resetSrc();
		}
		img.src = src;
	}

	function resetSrc(){
		setTimeout(function(){
			uploadPreview.tooltip('hide');
			uploadPreview.attr('data-original-title', null);
			$(uploadSrc.parent()).removeClass('confirmed');
		},0);
	}

	$('.gui-create-album').on('click', function(){
		var $this = $(this), title = $('.album-title'), desc = $('.album-description');
		if(!$this.hasClass('disabled'))
		{
			$this.addClass('disabled');
			var formData = new FormData();
			formData.append('Album[title]', title.val());
			formData.append('Album[description]', desc.val());

			$.ajax({
				url: $this.attr('data-href'),
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				data: formData,
				type: 'post',
				success: function(response){
					$this.removeClass('disabled');
					title.val(null);
					desc.val(null);
					if(response.result === 'success'){

						var html = $(albumTemplate
							.replace(/\{\{album_id\}\}/g, response.album.id)
							.replace(/\{\{album_title\}\}/g, response.album.title)
							.replace(/\{\{album_description\}\}/g, response.album.description)
							.replace(/\{\{album_status\}\}/g, response.album.status))
							.hide();
						html.attr('style', null);

						var option = '<option value="' + response.album.id + '">' + response.album.title + '</option>';

						$('.album-list').prepend(html.get(0));

						if($('.gui-albums').hasClass('hidden')){
							$('.gui-albums').removeClass('hidden');
						}

						var selects = photosBody.find('select');

						for (var i = 0; i < selects.length; i++) {
							$($(selects[i]).find('option')[0]).after(option);
						}

						var activeDetails = $(photosBody.find('.gui-info.active'));

						if(activeDetails.length){
							var currentSelect = $(activeDetails.find('select'));
							currentSelect.val(response.album.id)
						}

						toastr['success'](response.message);
					}else{
						alert(response.error);
					}
				}
			});
		}
	});

	container.on('click', '.delete-album', function(){
		var $this = $(this).addClass('disabled');
		if(confirm($this.attr('data-confirm')+'?')){
			$.getJSON($this.attr('href'), function(response){
				$this.removeClass('disabled');
				if(response.result === 'success'){

					var selects = photosBody.find('select[name=album]');

					for (var i = 0; i < selects.length; i++) {
						var options = $($(selects[i]).find('option'));
						for (var n = 0; n < options.length; n++) {
							var option = $(options[n]);
							if(option.val() == response.album.id){
								option.remove();
							}
						}
						
					}

					toastr['success'](response.message);
					$this.closest('li[draggable]').fadeOut(function(){
						$(this).remove();
						checkEmpty();
					});
					if($($this.closest('li[draggable]')).attr('data-key')){
						$this.closest('li[draggable]').fadeOut(function(){
							$(this).remove();
							checkEmpty();
						});
					}
				} else {
					alert(response.error);
				}
			});
		}
		return false;
	});

	container.on('input propertychange click', '.gui-author, .gui-author-src', function(){
		var radios = $(container.find('.ui-widget-body')).find('input[type=radio]');
		for (var i = 0, length = radios.length; i < length; i++) {
			if ($(radios[i]).val() == 2 && radios[i].checked != true){
				radios[i].checked = true;
			}
		}
	});

	photosBody.on('click', '.photo-info-panel', function(){
		var infoPanel = $($(this).closest('.gui-thumb')).siblings('.gui-info');

		for (var i = 0; i < $('.gui-info').length; i++) {
			if($($('.gui-info')[i]).hasClass('active')){
				$($('.gui-info')[i]).removeClass('active');
			}
		}

		if(infoPanel.hasClass('active')){
			infoPanel.removeClass('active');
		}else{
			infoPanel.addClass('active');
		}
	});

	photosBody.on('click', '[data-toggle=close]', function(){
		var infoPanel = $(this).closest('.gui-info');
			infoPanel.removeClass('active');
	});

	photosBody.on('change', '.photo-album', function(e){
		var target = $(e.target.selectedOptions[0]);
		if(target.attr('value') == 'album-create')
		{
			var albumsBtn = $(container.find('.album-modal'))
			albumsBtn.click();
		}
	});

	photosBody.on('input propertychange', '.photo-album,.photo-title,.photo-description,.photo-author,.photo-author-src', function(){
		var saveBtn = $($(this).closest('.gui-info')).find('.save-photo-description');
		if(saveBtn.hasClass('disabled')){
			saveBtn.removeClass('disabled').on('click', function(e){
				e.preventDefault();
				var $this = $(this).unbind('click').addClass('disabled'),
					li = $this.closest('li[draggable]'),
					album = $($this.closest('.gui-info')).find('.photo-album').val(),
					name = $($this.closest('.gui-info')).find('.photo-title').val(),
					text = $($this.closest('.gui-info')).find('.photo-description').val(),
					author = $($this.closest('.gui-info')).find('.photo-author').val(),
					author_src = $($this.closest('.gui-info')).find('.photo-author-src').val();
				// var primary = $($this.closest('.gui-info')).find('.photo-primary').prop('checked') ? 1 : 0;
				$.post(
					$this.attr('href'),
					{album: album, title: name, description: text, author: author, author_src: author_src/*, main: primary*/},
					function(response){
						if(response.result === 'success'){
							toastr['success'](response.message);
							li.find('.plugin-box').attr('title', text);
						}
						else{
							alert(response.error);
						}
					},
					'json'
				);
				return false;
			});
		}
	});

	photosBody.on('click', '.photo-primary', function(){
		var $this = $(this);
		if(confirm($this.attr('data-confirm')+'?'))
		{
			$this.val(1);
			// if($this.prop('checked'))
			// {
				var primary = $this.prop('checked') ? 1 : 0;
				$.post(
					$this.attr('data-href'),
					{main: primary},
					function(response){
						if(response.result === 'success'){
							toastr['success'](response.message);
						}
						else{
							alert(response.error);
						}
					},
					'json'
				);
			// }
		}
		return false;
	});

	photosBody.on('click', '.change-image-button', function(){
		$(this).parent().find('.change-image-input').trigger('click');
		return false;
	});

	photosBody.on('change', '.change-image-input', function(){
		var $this = $(this);
		var li = $this.closest('li[draggable]');
		var fileData = $this.prop('files')[0];
		var formData = new FormData();
		var changeButton = $this.siblings('.change-image-button').addClass('disabled');
		formData.append('Photo[image]', fileData);
		$.ajax({
			url: $this.siblings('.change-image-button').attr('href'),
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			data: formData,
			type: 'post',
			success: function(response){
				changeButton.removeClass('disabled');
				if(response.result === 'success'){
					li.find('.plugin-box').attr('href', response.photo.image).children('img').attr('src', response.photo.thumb);
					li.find('.gui-details-info').css({'--preview-image':'url(\'' + response.photo.thumb + '\');'});
					
					toastr['success'](response.message);
				}else{
					alert(response.error);
				}
			}
		});
	});

	photosBody.on('click', '[id*=_button_accept]', function(){
		var $this = $(this);
		var li = $this.closest('li[draggable]');
		var $this = $('#' + $(li.find('.uplaod-image-button')).attr('for'));
		var fileData = $this.prop('files')[0];
		var formData = new FormData();
		var uploadButton = $this.siblings('.uplaod-image-button').addClass('disabled');
		formData.append('Photo[image]', fileData);
		formData.append('Photo[image-cropping][aspectRatio]', 0);
		formData.append('Photo[image-cropping][dataRotate]', 0);
		formData.append('Photo[image-cropping][dataX]', 0);
		formData.append('Photo[image-cropping][dataY]', 0);
		formData.append('Photo[image-cropping][dataWidth]', 0);
		formData.append('Photo[image-cropping][dataHeight]', 0);

		$.ajax({
			url: $this.attr('href'),
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			data: formData,
			type: 'post',
			success: function(response){
				uploadButton.removeClass('disabled');
				if(response.result === 'success'){
					li.find('.plugin-box').attr('href', response.photo.image).children('img').attr('src', response.photo.thumb);
					toastr['success'](response.message);
				}else{
					alert(response.error);
				}
			}
		});
	});

	photosBody.on('click', '.delete-photo', function(){
		var $this = $(this).addClass('disabled');
		if(confirm($this.attr('data-confirm')+'?')){
			$.getJSON($this.attr('href'), function(response){
				$this.removeClass('disabled');
				if(response.result === 'success'){
					toastr['success'](response.message);
					$this.closest('li[draggable]').fadeOut(function(){
						$(this).remove();
						checkEmpty();
					});
					if($($this.closest('li[draggable]')).attr('data-key')){
						$this.closest('li[draggable]').fadeOut(function(){
							$(this).remove();
							checkEmpty();
						});
					}
				} else {
					alert(response.error);
				}
			});
		}
		return false;
	});

	selectAllButton.on('change', function(){
		var container = $($($(this).parents('.gui-widget')).find('.gui-sortable')),items=container.find('input.gui-selectable'),len,i;
		if(massCheckSelected(items)){
			for(i=0,len=items.length;i<len;i+=1){
				$(items[i]).prop('checked',false);
				selectAllButton.prop('checked',false);
				deleteButton.addClass('disabled');
			}
		}else{
			for(i=0,len=items.length;i<len;i+=1){
				$(items[i]).prop('checked',true);
				selectAllButton.prop('checked',true);
				deleteButton.removeClass('disabled');
			}
		}
	});

	photosBody.on('change', 'input.gui-selectable', function(){
		var container = $($($(this).parents('.gui-widget')).find('.gui-sortable')), items=container.find('input.gui-selectable');
		if(massCheckSelected(items)){
			deleteButton.removeClass('disabled');
		}else{
			deleteButton.addClass('disabled');
		}
	});

	container.on('click', '.gui-delete-selectable', function(){
		var container = $($($(this).parents('.gui-widget')).find('.gui-sortable')), inputs=container.find('input.gui-selectable');
		var $this = $(this).addClass('disabled');
		if(confirm($this.attr('data-confirm')+'?')){

			if(inputs.length)
			{
				var toLoad = [];
				for (var i = 0; i < inputs.length; i++) {
					value = inputs[i].value;
					if($(inputs[i]).prop('checked')){
						if(!toLoad.includes(value)){
							toLoad.push(value);
						}
					}else{
						delete toLoad[value];
					}
				}

				let proc, counter = 0, length = toLoad.length;
				var done = (function loop() {
					counter++;
					proc = (100 / length) * counter;
					var current = toLoad.shift();
					if (current)
					{
						return $.post(guiPhotoDeleteUrl + current).then(function(response){
							if(response.result === 'success'){
								toastr['success'](response.message);
								$('li[data-key='+current+']').fadeOut(function(){
									$(this).remove();
								});
							} else {
								alert(response.error);
							}
						}).then(loop);
					} else {
						return $.Deferred().resolve();
					}
				})();
			}
		}
		return false;
	});

	function massCheckSelected(items){
		for(i=0,len=items.length;i<len;i+=1){
			if(checkSelected(items[i])){
				return true;
			}
		}

		return false;
	}

	function checkSelected(item)
	{
		if(item.type&&item.type==='checkbox'){
			if($(item).is(':checked')){
				return true;
			}
		}
		return false;
	}

	function checkEmpty(){
		if(photosBody.find('li[data-key]').length) {
			if(!photosBody.is(':visible')) {
				// table.show();
				$('.empty').hide();
				$('.gui-sortable').show();
			}
		}
		else{
			// table.hide();
			$('.empty').show();
			$('.gui-sortable').hide();
		}
	}

	var dots = 0;
	function dotsAnimation() {
		dots = ++dots % 4;
		$("span", uploadingText).html(Array(dots+1).join("."));
	}
});