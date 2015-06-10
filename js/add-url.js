jQuery(document).ready(function ($) {
	$('#addFromURL_items .nojs').remove();
	$('#addFromURL_items .item').prepend('<div class="ui-state-default ui-corner-all" style="position:absolute; z-index:1; right:3.5em; top:1em; padding:0;"><a class="ui-icon ui-icon-close" title="Cancel Upload" href="#" onclick="addFromURL_cancel(this.parentNode.parentNode); return false;"></a></div>');
	$('#flgalleryAddMedia').css('backgroundImage', 'none');
	$('#flgalleryAddMediaForm').fadeIn(500);
	$('#addFromURL .add .button').removeAttr('disabled');
});


var addFromURL_itemsCount = 1;

function addFromURL_cancel(e) {
	jQuery(document).ready(function ($) {
		$(e).fadeOut(500, function () {
			$(e).remove();
		});
	});
}

function addFromURL_addItem() {
	jQuery(document).ready(function ($) {
		addFromURL_itemsCount++;
		var item = $('#addFromURL_items #addFromURL_item-0').clone();
		item
			.attr('id', 'addFromURL_item-' + addFromURL_itemsCount)
			.html(item.html().replace(/-id-0/g, '-id-' + addFromURL_itemsCount))
			.appendTo('#addFromURL_items')
			.show(500);
	});
}
