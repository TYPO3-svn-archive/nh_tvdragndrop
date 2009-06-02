tx_nhtvdragndrop = function() {
	var	url = '';
	var currentItem;
	var pub = {};

	debug = function(output) {
		if ($('tx_nhtvdragndrop-debug') == null)
			$('ext-templavoila-mod1-index-php').insert({
				top: '<div id="tx_nhtvdragndrop-debug"></div>'});

		$('tx_nhtvdragndrop-debug').insert({bottom: output + '<br />'});
	};

	getContainerIdByPointer = function(pointer) {
		return pointer.substring(0, pointer.lastIndexOf(':') + 1);
	};

	getIdByPointer = function(pointer) {
		return 'tx_nhtvdragndrop-item_' + pointer;
	};

	getPointerById = function(id) {
		return id.split('tx_nhtvdragndrop-item_')[1];
	};

	change = function(el) {
		currentItem = el;
	};

	 //todo: evaluate use of regExp instead of split.
	rewriteButton = function(button, splitBy, newPointer) {
		if ((p = button.href.split(splitBy)).length == 2) {
			button.href = p[0] + splitBy + newPointer;
			return true;
		}
		return false;
	};

	 //todo: find a better way to rewrite.
	updateItemButtons = function(item, container, index) {
		var newPointer = container.id + index;
		var itemChilds = item.childElements();
		var buttonBar =
			itemChilds[0].childElements()[0].childElements()[0].childElements()[1];

		buttonBar.select('a').each(function(button) {
			if (button.href.charAt(button.href.length - 1) == "#")
				return;

			if ((p = button.href.split('unlinkRecord')).length == 2) {
				button.href = p[0] + 'unlinkRecord("' + newPointer + '");';
				return
			}

			if((p = button.href.split('CB[el][tt_content')).length == 2) {
				p1 = p[1].split('=');
				if (p1[1] != '1')
					button.href = p[0] + 'CB[el][tt_content' + p1[0]+ '=' + newPointer;
			}

		});

		 //New button
		rewriteButton(itemChilds[1], '&parentRecord=', newPointer);

		 //Paste button
		if (itemChilds[2])
			rewriteButton(itemChilds[2], '&destination=', newPointer);

	};

	updateContainer = function(container) {
		var index = 0;
		var pastButtons;
		container.childElements().each(function(item){
			if (item.id.indexOf('tx_nhtvdragndrop-item_') == -1)
				return;

			if (currentItem && item.id == currentItem.id) {
				new Ajax.Request(url +
					'&ajaxID=tx_nhtvdragndrop_ajax::moveRecord&source=' +
					getPointerById(item.id)	+
					"&destination=" + (container.id + index));

				currentItem = false;

				 //todo: and some optimisation (source not changed etc.).
				if (pasteButtons = $$('a[href*="source"]')) {
					pasteButtons.each(function(button) {
						var queryParms = button.href.toQueryParams();
							//todo: find a better way
						if (queryParms['pasteRecord'] == 'ref');
								return;

						queryParms['source'] = container.id + (index + 1);
						button.href =  'index.php?' + $H(queryParms).toQueryString();
					});
				}
			}

			index++;
			updateItemButtons(item, container, index);
			item.id = getIdByPointer(container.id + index);
		});

	};

	pub.init = function(containers, linkParameters, siteRelPath) {
		url =  siteRelPath + 'ajax.php?' +  linkParameters;
		containers.each(function(c) {
			Sortable.create(c, {
				tag: 'div',
				handle: 'sortable_handle',
				dropOnEmpty: true,
				constraint: false,
				onChange: change,
				onUpdate: updateContainer,
				containment: containers});
		});
	};

	pub.unlinkRecord = function(pointer) {
		new Ajax.Request(url +
			'&ajaxID=tx_nhtvdragndrop_ajax::unlinkRecord&unlink=' + pointer, {
				onComplete: function() {
					$(getIdByPointer(pointer)).remove();
					updateContainer($(getContainerIdByPointer(pointer)));
				}
			}
		);

	};

	return pub;
}();