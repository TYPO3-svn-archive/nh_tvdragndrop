tx_nhtvdragndrop = function() {
	var url = '';
	var currentItem;
	var showHiddenElements;
	var pub = {};

	var getContainerIdByPointer = function(pointer) {
		return pointer.substring(0, pointer.lastIndexOf(':') + 1);
	};

	var getIdByPointer = function(pointer) {
		return 'tx_nhtvdragndrop-item_' + pointer;
	};

	var getPointerById = function(id) {
		return id.split('tx_nhtvdragndrop-item_')[1];
	};

	var change = function(el) {
		currentItem = el;
	};

	 //TODO: Evaluate use of regExp instead of split.
	var rewriteButton = function(button, splitBy, newPointer) {
		var p;
		if ((p = button.href.split(splitBy)).length == 2) {
			button.href = p[0] + splitBy + newPointer;
			return true;
		}
		return false;
	};

	 //TODO: Find a better way to rewrite.
	var updateItemButtons = function(item, container, index) {
		var itemChilds = item.childElements();
		if (itemChilds.length == 0)
			return;

		var p, p1;
		var newPointer = getPointerById(container.id) + index;

		var buttonBar =
			itemChilds[0].childElements()[0].childElements()[0].childElements()[1];

		buttonBar.select('a').each(function(button) {

			if (button.href.charAt(button.href.length - 1) == "#") {
				return;
			}

			if ((p = button.href.split('unlinkRecord')).length == 2) {
				button.href = p[0] + 'unlinkRecord("' + newPointer + '");';
				return;
			}

			if((p = button.href.split('CB[el][tt_content')).length == 2) {
				p1 = p[1].split('=');
				if (p1[1] != '1') {
					button.href = p[0] + 'CB[el][tt_content' + p1[0]+ '=' + newPointer;
				}
			}

		});

		 //New button
		rewriteButton(itemChilds[1], '&parentRecord=', newPointer);

		 //Paste button
		if (itemChilds[3]) {
			rewriteButton(itemChilds[3], '&destination=', newPointer);
		}
	};

	var updateContainer = function(container) {
		var index = 0;
		var pasteButtons;
		var containerPointer = getPointerById(container.id);

		container.childElements().each(function(item){
			if (item.id.indexOf('tx_nhtvdragndrop-item_') == -1) {
				return;
			}

			if (currentItem && item.id == currentItem.id) {
				new Ajax.Request(url +
					'&ajaxID=tx_nhtvdragndrop_ajax::moveRecord&source=' +
					getPointerById(item.id) +
					"&destination=" + (containerPointer + index));

				currentItem = false;


				 //TODO: Add some optimisation (source not changed etc.).
				if ((pasteButtons = $$('a[href*="source"]'))) {
					pasteButtons.each(function(button) {
						var queryParms = button.href.toQueryParams();
						 //TODO: Find a better way
						if (queryParms.pasteRecord == 'ref') {
							return;
						}

						queryParms.source = containerPointer + (index + 1);
						button.href =  'index.php?' + $H(queryParms).toQueryString();
					});
				}
			}
			 //TODO: Find a way to handle problem with "hidden unused items"
			index++;
			updateItemButtons(item, container, index);
			item.id = getIdByPointer(containerPointer + index);
		});

	};

	pub.init = function(containers, linkParameters, siteRelPath, showHidden) {
		url =  siteRelPath + 'ajax.php?' + linkParameters;
		showHiddenElements = showHidden;

		 //TODO: Reactived handle option by the time prototype has been upgraded to 1.6.1
		containers.each(function(c) {
			Sortable.create(c, {
				tag: 'div',
				/* handle: 'sortable_handle', */
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

	/**
	 * Hide the record. Skeleton taken from the orginal TV js code. Thanks guys :)
	 */
	pub.hideRecord= function(element, command) {
		if (showHiddenElements) {
			jumpToUrl(command);
		} else {
			var sortableItem = element.up('div.sortableItem',0);
			 //TODO: Calling TCE_DB via ajax is a little costy. Find a better way.
			new Ajax.Request(command);
			new Effect.Fade(sortableItem, {duration: 0.5, afterFinish: function () {
				 // We need to truncate the surrounding div instead of removing it
				 // in order to keep the element order.
				sortableItem.update('');
			}});


		}
	};

	/**
	 * Unhide the record. Taken from the orginal TV js code. Thanks guys :)
	 */
	pub.unhideRecord= function(element, command) {
		jumpToUrl(command);
	};

	return pub;
}();