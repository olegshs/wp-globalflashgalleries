jQuery(document).ready(function($) {
	$('#flgalleryAddMedia').css('backgroundImage', 'none');
	$('#flgalleryAddMediaForm').fadeIn(500);

	/**
	 * Scale
	 */
	var Scale = {
	    /**
	     * Fill
	     * @param {number|*} w1
	     * @param {number|*} h1
	     * @param {number|*} w2
	     * @param {number|*} h2
	     * @returns {Object.<string, number>}
	     */
	    fill: function (w1, h1, w2, h2) {
	        w1 = Number(w1);
	        h1 = Number(h1);
	        w2 = Number(w2);
	        h2 = Number(h2);

	        var w, h, x, y, k = w1 / h1;

	        w = w2;
	        if (w > w1) {
	            w = w1;
	        }

	        h = w / k;
	        if (h < h2) {
	            h = h2;
	            w = h * k;
	        }

	        x = (w2 - w) / 2;
	        y = (h2 - h) / 2;

	        return {
	            'left': x,
	            'top': y,
	            'width': w,
	            'height': h
	        };
	    }
	};

	function loadItems(offset, limit) {
		$.ajax({
			url: flgallery.adminAjax,
			type: 'get',
			data: {
				action: 'flgalleryAdmin',
				ajax_action: 'getWpMediaLibraryJson',
				offset: offset,
				limit: limit
			},
			dataType: 'json',
			success: function(response) {
				var ul = $('#importWpMedia-items'), li, img;
				var i, item, checkbox;

				for (i = 0; i < response.length; i++) {
					item = response[i];

					li = $('<li>');

					img = $('<img>');
					img.attr({
						src: item.thumbnail.src,
						width: item.thumbnail.width,
						height: item.thumbnail.height
					}).css(Scale.fill(item.thumbnail.width, item.thumbnail.height, 150, 150));

					checkbox = $('<input type="checkbox">');
					checkbox.val(item.ID);

					li.append(img);
					li.append(checkbox);
					ul.append(li);
				}
			}
		});
	}

	var offset = 0, limit = 30;

	function loadMore() {
		loadItems(offset, limit);
		offset += limit;
	}

	loadMore();
	$('#importWpMedia .button.more').click(loadMore);
});
