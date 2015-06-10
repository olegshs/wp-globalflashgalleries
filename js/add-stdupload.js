function stdUpload_cancel(element) {
	jQuery(element).parents('.item').fadeOut(500, function () {
		jQuery(this).remove();
	});
}

(function ($) {
	var itemsCount = 1;

	function addItem() {
		itemsCount++;
		var item = $('#stdUpload_items #stdUpload_item-0').clone();
		item
			.attr('id', 'stdUpload_item-' + itemsCount)
			.html(item.html().replace(/-id-0/g, '-id-' + itemsCount))
			.appendTo('#stdUpload_items')
			.show(500);

		$('#stdUpload_items .item .file').change(function () {
			changeItem(this);
		});
	};

	var changedItems = [];

	function changeItem(element) {
		var id = parseInt(element.id.match(/\d+/));

		document.getElementById('stdUpload_title-id-' + id).value =
			(element.value.match(/^(.*[\/\\]|)(.*)\.(.*?)$/)[2]).replace(/[_-]/g, ' ').replace(/\s+/g, ' ').replace(/^\s\s*/, '').replace(/\s\s*$/, '');

		if (changedItems[id] == undefined) {
			changedItems[id] = true;
			if ($(element).parents('.item').next('.item').length == 0) {
				addItem();
			}
		}
	}

	$('#stdUpload_items .nojs').remove();
	$('#stdUpload_items .item').prepend('<div class="ui-state-default ui-corner-all" style="position:absolute; z-index:1; right:3.5em; top:1em; padding:0;"><a class="cancel-upload ui-icon ui-icon-close" title="Cancel Upload" href="#" onclick="stdUpload_cancel(this); return false;"></a></div>');
	$('#stdUpload_items .item .file').change(function () {
		changeItem(this);
	});
	$(document).ready(function ($) {
		$('#flgalleryAddMedia').css('backgroundImage', 'none');
		$('#flgalleryAddMediaForm').fadeIn(500);
		$('#stdUpload .add .button').removeAttr('disabled');
	});

	$('#stdUpload .add .button')
		.click(function () {
			addItem();
		}).removeAttr('disabled');

})(jQuery);
