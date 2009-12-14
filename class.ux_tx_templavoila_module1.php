<?php
class ux_tx_templavoila_module1 extends tx_templavoila_module1 {
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH;

		if (!is_callable(array('t3lib_div', 'int_from_ver')) || t3lib_div::int_from_ver(TYPO3_version) < 4000000) {
			$this->content = 'Fatal error:This version of TemplaVoila does not work with TYPO3 versions lower than 4.0.0! Please upgrade your TYPO3 core installation.';
			return;
		}

			// Access check! The page will show only if there is a valid page and if this page may be viewed by the user
		if (is_array($this->altRoot))	{
			$access = true;
				// get PID of altRoot Element to get pageInfoArr
			$altRootRecord = t3lib_BEfunc::getRecordWSOL ($this->altRoot['table'], $this->altRoot['uid'], 'pid');
			$pageInfoArr = t3lib_BEfunc::readPageAccess ($altRootRecord['pid'], $this->perms_clause);
		} else {
			$pageInfoArr = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
			$access = (intval($pageInfoArr['uid'] > 0));
		}

		if ($access) {

			$this->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageInfoArr);

				// Define the root element record:
			$this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
			$this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->id;
			$this->rootElementRecord = t3lib_BEfunc::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
			if ($this->rootElementRecord['t3ver_swapmode']==0 && $this->rootElementRecord['_ORIG_uid'] ) {
				$this->rootElementUid_pidForContent = $this->rootElementRecord['_ORIG_uid'];
			}else{
				// If pages use current UID, otherwhise you must use the PID to define the Page ID
				if ($this->rootElementTable == 'pages') {
					$this->rootElementUid_pidForContent = $this->rootElementRecord['uid'];
				}else{
					$this->rootElementUid_pidForContent = $this->rootElementRecord['pid'];
				}
			}
				// Check if we have to update the pagetree:
			if (t3lib_div::_GP('updatePageTree')) {
				t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
			}

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('noDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->doc->divClass = '';
			$this->doc->form='<form action="'.htmlspecialchars('index.php?'.$this->link_getParameters()).'" method="post" autocomplete="off">';

				// Adding classic jumpToUrl function, needed for the function menu. Also, the id in the parent frameset is configured.
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function jumpToUrl(URL)	{ //
					document.location = URL;
					return false;
				}
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			' . $this->doc->redirectUrls() . '
							function jumpToUrl(URL)	{	//
								window.location.href = URL;
								return false;
							}
							function jumpExt(URL,anchor)	{	//
								var anc = anchor?anchor:"";
								window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
								return false;
							}
							function jumpSelf(URL)	{	//
								window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
								return false;
							}

							function setHighlight(id)	{	//
								top.fsMod.recentIds["web"]=id;
								top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

								if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
									top.content.nav_frame.refresh_nav();
								}
							}

							function editRecords(table,idList,addParams,CBflag)	{	//
								window.location.href="'.$BACK_PATH.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).
									'&edit["+table+"]["+idList+"]=edit"+addParams;
							}
							function editList(table,idList)	{	//
								var list="";

									// Checking how many is checked, how many is not
								var pointer=0;
								var pos = idList.indexOf(",");
								while (pos!=-1)	{
									if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
										list+=idList.substr(pointer,pos-pointer)+",";
									}
									pointer=pos+1;
									pos = idList.indexOf(",",pointer);
								}
								if (cbValue(table+"|"+idList.substr(pointer))) {
									list+=idList.substr(pointer)+",";
								}

								return list ? list : idList;
							}

							if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
						'

			);

			$this->doc->JScode .= '<script src="' . $this->doc->backPath . 'contrib/scriptaculous/scriptaculous.js?load=effects,dragdrop" type="text/javascript"></script>';
			$this->doc->loadJavascriptLib('../' . t3lib_extMgm::siteRelPath('nh_tvdragndrop') .
				'js/tx_nhtvdragndrop-min.js');

				// Set up JS for dynamic tab menu and side bar
			$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
			$this->doc->JScode .= $this->modTSconfig['properties']['sideBarEnable'] ? $this->sideBarObj->getJScode() : '';

				// Setting up support for context menus (when clicking the items icon)
			$CMparts = $this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];

			// CSS for drag and drop
			$this->doc->inDocStyles .= '
				table {position:relative;}
				.sortable_handle {cursor:move;}
			';

			if (t3lib_extMgm::isLoaded('t3skin')) {
				// Fix padding for t3skin in disabled tabs
				$this->doc->inDocStyles .= '
					table.typo3-dyntabmenu td.disabled, table.typo3-dyntabmenu td.disabled_over, table.typo3-dyntabmenu td.disabled:hover { padding-left: 10px; }
				';
			}

			$this->handleIncomingCommands();

				// Start creating HTML output
			$this->content .= $this->doc->startPage($LANG->getLL('title'));
			$render_editPageScreen = true;
				// Show message if the page is of a special doktype:
			if ($this->rootElementTable == 'pages') {

					// Initialize the special doktype class:
				$specialDoktypesObj =& t3lib_div::getUserObj ('&tx_templavoila_mod1_specialdoktypes','');
				$specialDoktypesObj->init($this);

				$methodName = 'renderDoktype_'.$this->rootElementRecord['doktype'];
				if (method_exists($specialDoktypesObj, $methodName)) {
					$result = $specialDoktypesObj->$methodName($this->rootElementRecord);
					if ($result !== FALSE) {
						$this->content .= $result;
						if ($GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'edit')) {
							// Edit icon only if page can be modified by user
							$this->content .= '<br/><br/><strong>'.$this->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="border: none; vertical-align: middle" /> '.$LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage'),'pages',$this->id).'</strong>';
						}
						$render_editPageScreen = false; // Do not output editing code for special doctypes!
					}
				}
			}

			if ($render_editPageScreen) {
					// Render "edit current page" (important to do before calling ->sideBarObj->render() - otherwise the translation tab is not rendered!
				$editCurrentPageHTML = $this->render_editPageScreen();

					// Hook for adding new sidebars or removing existing
				$sideBarHooks = $this->hooks_prepareObjectsArray('sideBarClass');
				foreach ($sideBarHooks as $hookObj)	{
					if (method_exists($hookObj, 'main_alterSideBar')) {
						$hookObj->main_alterSideBar($this->sideBarObj, $this);
					}
				}

					// Show the "edit current page" screen along with the sidebar
				$shortCut = ($BE_USER->mayMakeShortcut() ? '<br /><br />'.$this->doc->makeShortcutIcon('id,altRoot',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']) : '');
				if ($this->sideBarObj->position == 'left' && $this->modTSconfig['properties']['sideBarEnable']) {
					$this->content .= '
						<table cellspacing="0" cellpadding="0" style="width:100%; height:550px; padding:0; margin:0;">
							<tr>
								<td style="vertical-align:top;">'.$this->sideBarObj->render().'</td>
								<td style="vertical-align:top; padding-bottom:20px;" width="99%">'.$editCurrentPageHTML.$shortCut;'</td>
							</tr>
						</table>
					';
				} else {
					$sideBarTop = $this->modTSconfig['properties']['sideBarEnable']  && ($this->sideBarObj->position == 'toprows' || $this->sideBarObj->position == 'toptabs') ? $this->sideBarObj->render() : '';
					$this->content .= $sideBarTop.$editCurrentPageHTML.$shortCut;
				}

				// Create sortables
				if (is_array($this->sortableContainers)) {
					$this->content .= $this->doc->wrapScriptTags(
						'document.observe("dom:loaded", function() { ' .
						'tx_nhtvdragndrop.init([\'' .
						implode('\',\'', $this->sortableContainers) . '\'], \'' .
						$this->link_getParameters() .
						'\', \'' . $this->doc->backPath . '\', ' .
						($this->MOD_SETTINGS['tt_content_showHidden'] ? 1 : 0) .');})');
				}
			}

		} else {	// No access or no current page uid:

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->docType= 'xhtml_trans';
			$this->doc->backPath = $BACK_PATH;
			$this->content.=$this->doc->startPage($LANG->getLL('title'));

			$cmd = t3lib_div::_GP ('cmd');
			switch ($cmd) {

					// Create a new page
				case 'crPage' :
						// Output the page creation form
					$this->content .= $this->wizardsObj->renderWizard_createNewPage (t3lib_div::_GP ('positionPid'));
					break;

					// If no access or if ID == zero
				default:
					$this->content.=$this->doc->header($LANG->getLL('title'));
					$this->content.=$LANG->getLL('default_introduction');
			}
		}
		$this->content.=$this->doc->endPage();
	}

	/**
	 * Renders the sub elements of the given elementContentTree array. This function basically
	 * renders the "new" and "paste" buttons for the parent element and then traverses through
	 * the sub elements (if any exist). The sub element's (preview-) content will be rendered
	 * by render_framework_singleSheet().
	 *
	 * Calls render_framework_allSheets() and therefore generates a recursion.
	 *
	 * @param	array		$elementContentTreeArr: Content tree starting with the element which possibly has sub elements
	 * @param	string		$languageKey: Language key for current display
	 * @param	string		$sheet: Key of the sheet we want to render
	 * @return	string		HTML output (a table) of the sub elements and some "insert new" and "paste" buttons
	 * @access protected
	 * @see render_framework_allSheets(), render_framework_singleSheet()
	 */
	function render_framework_subElements($elementContentTreeArr, $languageKey, $sheet){
		global $LANG;

		$beTemplate = '';
		$flagRenderBeLayout = false;

			// Define l/v keys for current language:
		$langChildren = intval($elementContentTreeArr['ds_meta']['langChildren']);
		$langDisable = intval($elementContentTreeArr['ds_meta']['langDisable']);

		$lKey = $langDisable ? 'lDEF' : ($langChildren ? 'lDEF' : 'l'.$languageKey);
		$vKey = $langDisable ? 'vDEF' : ($langChildren ? 'v'.$languageKey : 'vDEF');

		if (!is_array($elementContentTreeArr['sub'][$sheet]) || !is_array($elementContentTreeArr['sub'][$sheet][$lKey])) return '';

		$output = '';
		$cells = array();
		$headerCells = array();

				// gets the layout
		$beTemplate = $elementContentTreeArr['ds_meta']['beLayout'];

				// no layout, no special rendering
		$flagRenderBeLayout = $beTemplate? TRUE : FALSE;

			// Traverse container fields:
		foreach($elementContentTreeArr['sub'][$sheet][$lKey] as $fieldID => $fieldValuesContent)	{
			if ($elementContentTreeArr['previewData']['sheets'][$sheet][$fieldID]['isMapped'] && is_array($fieldValuesContent[$vKey]))	{
				$fieldContent = $fieldValuesContent[$vKey];

				$cellContent = '';

					// Create flexform pointer pointing to "before the first sub element":
				$subElementPointer = array (
					'table' => $elementContentTreeArr['el']['table'],
					'uid' => $elementContentTreeArr['el']['uid'],
					'sheet' => $sheet,
					'sLang' => $lKey,
					'field' => $fieldID,
					'vLang' => $vKey,
					'position' => 0
				);

				$canCreateNew = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'new');
				$canEditContent = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'editcontent');

				if (!$this->translatorMode && $canCreateNew)	{

						// "New" and "Paste" icon:
					$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
					$cellContent .= $this->link_new($newIcon, $subElementPointer);
					$cellContent .= '<span class="sortablePaste">' . $this->clipboardObj->element_getPasteButtons ($subElementPointer) . '</span>';
				}

					// Render the list of elements (and possibly call itself recursively if needed):
				if (is_array($fieldContent['el_list'])) {
					foreach($fieldContent['el_list'] as $position => $subElementKey)	{
						$subElementArr = $fieldContent['el'][$subElementKey];

						if ((!$subElementArr['el']['isHidden'] || $this->MOD_SETTINGS['tt_content_showHidden']) && $this->displayElement($subElementArr))	{

								// When "onlyLocalized" display mode is set and an alternative language gets displayed
							if (($this->MOD_SETTINGS['langDisplayMode'] == 'onlyLocalized') && $this->currentLanguageUid>0)	{

									// Default language element. Subsitute displayed element with localized element
								if (($subElementArr['el']['sys_language_uid']==0) && is_array($subElementArr['localizationInfo'][$this->currentLanguageUid]) && ($localizedUid = $subElementArr['localizationInfo'][$this->currentLanguageUid]['localization_uid']))	{
									$localizedRecord = t3lib_BEfunc::getRecordWSOL('tt_content', $localizedUid, '*');
									$tree = $this->apiObj->getContentTree('tt_content', $localizedRecord);
									$subElementArr = $tree['tree'];
								}
							}
							$this->containedElements[$this->containedElementsPointer]++;

								// Modify the flexform pointer so it points to the position of the curren sub element:
							$subElementPointer['position'] = $position;

							$cellContent .= $this->render_framework_allSheets($subElementArr, $languageKey, $subElementPointer, $elementContentTreeArr['ds_meta']);

							if (!$this->translatorMode && $canCreateNew) {
									// "New" and "Paste" icon:
								$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
								$cellContent .= $this->link_new($newIcon, $subElementPointer);

								$cellContent .= '<span class="sortablePaste">' . $this->clipboardObj->element_getPasteButtons ($subElementPointer) . '</span></div>';
							}

						} else {
								// Modify the flexform pointer so it points to the position of the curren sub element:
							$subElementPointer['position'] = $position;

							if ($canEditContent) {
								$cellId = $this->addSortableItem ($this->apiObj->flexform_getStringFromPointer ($subElementPointer));
								$cellFragment = '<div class="sortableItem" id="' . $cellId . '"></div>';
							}

							$cellContent .= $cellFragment;

						}
					}
				}

				$cellIdStr = '';
				if ($GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'editcontent')) {
					$tmpArr = $subElementPointer;
					unset($tmpArr['position']);
					$cellId = $this->addSortableItem ($this->apiObj->flexform_getStringFromPointer ($tmpArr));
					$cellIdStr = ' id="' . $cellId . '"';
					$this->sortableContainers[] = $cellId;
				}

					// Add cell content to registers:
				if ($flagRenderBeLayout==TRUE) {
					$beTemplateCell = '<table width="100%" class="beTemplateCell"><tr><td valign="top" style="background-color: '.$this->doc->bgColor4.'; padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td></tr><tr><td '.$cellIdStr.' valign="top" style="padding: 5px;">'.$cellContent.'</td></tr></table>';
					$beTemplate = str_replace('###'.$fieldID.'###', $beTemplateCell, $beTemplate);
				} else {
							// Add cell content to registers:
					$headerCells[]='<td valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet][$lKey])).'%" style="background-color: '.$this->doc->bgColor4.'; padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td>';
					$cells[]='<td '.$cellIdStr.' valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet][$lKey])).'%" style="border: 1px dashed #000; padding: 5px 5px 5px 5px;">'.$cellContent.'</td>';
				}
			}
		}

		if ($flagRenderBeLayout) {
			// removes not used markers
			$beTemplate = preg_replace("/###field_.*?###/", '', $beTemplate);
			return $beTemplate;
		}

			// Compile the content area for the current element (basically what was put together above):
		if (count ($headerCells) || count ($cells)) {
			$output = '
				<table border="0" cellpadding="2" cellspacing="2" width="100%">
					<tr>'.(count($headerCells) ? implode('', $headerCells) : '<td>&nbsp;</td>').'</tr>
					<tr>'.(count($cells) ? implode('', $cells) : '<td>&nbsp;</td>').'</tr>
				</table>
			';
		}

		return $output;
	}


	/**
	 * Returns an HTML link for unlinking a content element. Unlinking means that the record still exists but
	 * is not connected to any other content element or page.
	 *
	 * @param	string		$label: The label
	 * @param	array		$unlinkPointer: Flexform pointer pointing to the element to be unlinked
	 * @param	boolean		$realDelete: If set, the record is not just unlinked but deleted!
	 * @param   boolean     $foreignReferences: If set, the record seems to have references on other pages
	 * @return	string		HTML anchor tag containing the label and the unlink-link
	 * @access protected
	 */
	function link_unlink($label, $unlinkPointer, $realDelete=FALSE, $foreignReferences=FALSE)	{

		$unlinkPointerString = rawurlencode($this->apiObj->flexform_getStringFromPointer ($unlinkPointer));

		if ($realDelete)	{
			$LLlabel = $foreignReferences ? 'deleteRecordWithReferencesMsg' : 'deleteRecordMsg';
			return '<a href="index.php?' . $this->link_getParameters() . '&amp;deleteRecord=' . $unlinkPointerString . '" onclick="' . htmlspecialchars('return confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL($LLlabel)) . ');') . '">' . $label . '</a>';
		} else {
			return '<a href="javascript:'.htmlspecialchars('if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('unlinkRecordMsg')) . '))') . 'tx_nhtvdragndrop.unlinkRecord(\'' . $unlinkPointerString . '\');">' . $label . '</a>';
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$label: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$hidden: ...
	 * @param	[type]		$forced: ...
	 * @return	[type]		...
	 */
	function link_hide($label, $table, $uid, $hidden, $forced=FALSE) {
		if ($label) {
			if (($table == 'pages' && ($this->calcPerms & 2) ||
				 $table != 'pages' && ($this->calcPerms & 16)) &&
				(!$this->translatorMode || $forced))	{
					if ($table == "pages" && $this->currentLanguageUid) {
						$params = '&data['.$table.']['.$uid.'][hidden]=' . (1 - $hidden);
					//	return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';
					} else {
						$params = '&data['.$table.']['.$uid.'][hidden]=' . (1 - $hidden);
					//	return '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '">'.$label.'</a>';

						/* the commands are indipendent of the position,
						 * so sortable doesn't need to update these and we
						 * can safely use '#'
						 */
						if ($hidden)
							return '<a href="#" onclick="tx_nhtvdragndrop.unhideRecord(this, \'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');">' . $label . '</a>';
						else
							return '<a href="#" onclick="tx_nhtvdragndrop.hideRecord(this, \'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');">' . $label . '</a>';
					}
				} else {
					return $label;
				}
		}
		return '';
	}

	/**
	 * Adds a flexPointer to the stack of sortable items for drag&drop
	 *
	 * @param string   the sourcePointer for the referenced element
	 * @return string the key for the related html-element
	 */
	function addSortableItem($pointerStr) {
		$key = 'tx_nhtvdragndrop-item_' . $pointerStr;
		$this->sortableItems[$key] = $pointerStr;
		return $key;
	}
}
?>