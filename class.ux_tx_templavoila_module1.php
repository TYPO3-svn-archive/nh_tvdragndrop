<?php
class ux_tx_templavoila_module1 extends tx_templavoila_module1 {

	/*******************************************
	 *
	 * Main functions
	 *
	 *******************************************/

	/**
	 * Main function of the module.
	 *
	 * @return	void
	 * @access public
	 */
	var $sortable_containers=Array();
	function main()    {
		global $BE_USER,$LANG,$BACK_PATH;

		if (!is_callable(array('t3lib_div', 'int_from_ver')) || t3lib_div::int_from_ver(TYPO3_version) < 4000000) {
			$this->content = 'Fatal error:This version of TemplaVoila does not work with TYPO3 versions lower than 4.0.0! Please upgrade your TYPO3 core installation.';
			return;
		}

			// Access check! The page will show only if there is a valid page and if this page may be viewed by the user
		if (is_array($this->altRoot))	{
			$access = true;
		} else {
			$pageInfoArr = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
			$access = (intval($pageInfoArr['uid'] > 0));
		}

		if ($access)    {

			if (t3lib_div::_GP("ajaxPasteRecord") == 'cut') {
				$sourcePointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('source'));
				$destinationPointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('destination'));
				$this->apiObj->moveElement ($sourcePointer, $destinationPointer);
				exit;
			}

			if (t3lib_div::_GP("ajaxUnlinkRecord")) {
				$unlinkDestinationPointer = $this->apiObj->flexform_getPointerFromString (t3lib_div::_GP('ajaxUnlinkRecord'));
				$this->apiObj->unlinkElement($unlinkDestinationPointer);
				exit;
			}


			$this->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageInfoArr);
				// Define the root element record:
			$this->rootElementTable = is_array($this->altRoot) ? $this->altRoot['table'] : 'pages';
			$this->rootElementUid = is_array($this->altRoot) ? $this->altRoot['uid'] : $this->id;
			$this->rootElementRecord = t3lib_BEfunc::getRecordWSOL($this->rootElementTable, $this->rootElementUid, '*');
			$this->rootElementUid_pidForContent = $this->rootElementRecord['t3ver_swapmode']==0 && $this->rootElementRecord['_ORIG_uid'] ? $this->rootElementRecord['_ORIG_uid'] : $this->rootElementRecord['uid'];


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

				//Prototype /Scriptaculous
			$this->doc->JScode .= '<script src="'.$this->doc->backPath.t3lib_extMgm::extRelPath('nh_tvdragndrop').'js/prototype.js" type="text/javascript"></script>';
			$this->doc->JScode .= '<script src="'.$this->doc->backPath.t3lib_extMgm::extRelPath('nh_tvdragndrop').'js/scriptaculous/scriptaculous.js?load=effects,dragdrop" type="text/javascript"></script>';
				// Set up JS for dynamic tab menu and side bar
			$this->doc->JScode .= $this->doc->getDynTabMenuJScode();
			$this->doc->JScode .= $this->modTSconfig['properties']['sideBarEnable'] ? $this->sideBarObj->getJScode() : '';

				// Setting up support for context menus (when clicking the items icon)
			$CMparts = $this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];
				// IE resize workaround

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
				foreach ($sideBarHooks as $hookObj) {
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

				 //Create sortables
				if (is_array($this->sortable_containers)) {
					//$this->content .='<div id="sortableDebug"></div>';
					$this->content .='
					<script type="text/javascript" language="javascript">
					<!--
					var sortable_currentItem;

					function sortable_unlinkRecordCallBack(obj) {
						var el = obj.element;
						var pn = el.parentNode;
						//alert (pn.id);
						pn.removeChild(el);
						sortable_update(pn);
					}

					function sortable_unlinkRecord(id) {
						new Ajax.Request("index.php?'.$this->link_getParameters().'&ajaxUnlinkRecord="+escape(id));
						new Effect.Fade(id,
							{ duration: 0.5,
							  afterFinish: sortable_unlinkRecordCallBack });
					}

					function sortable_updateItemButtons(el, position, pID) {
						var p	= new Array();	var p1 = new Array();
						var href = "";	var i=0;
						var newPos = escape(pID + position);
						var childs = el.childElements()
						var buttons = childs[0].childElements()[0].childElements()[0].childElements()[1].childNodes;
						for (i = 0; i < buttons.length ;i++) {
							if (buttons[i].nodeType != 1) continue;
							href = buttons[i].href;
							//alert(href);
							if (href.charAt(href.length - 1) == "#") continue;
							if ((p = href.split("unlinkRecord")).length == 2) {
								buttons[i].href = p[0] + "unlinkRecord(\'" + newPos + "\');";
							} else if((p = href.split("CB[el][tt_content")).length == 2) {
								p1 = p[1].split("=");
								buttons[i].href = p[0] + "CB[el][tt_content" + p1[0]+ "="  + newPos;
							} else if ((p = href.split("&parentRecord=")).length == 2) {
								buttons[i].href = p[0] + "&parentRecord=" + newPos;
							} else if ((p = href.split("&destination=")).length == 2) {
								buttons[i].href = p[0] + "&destination=" + newPos;
							}
						}

						if ((p = childs[1].href.split("&parentRecord=")).length == 2)
								childs[1].href = p[0] + "&parentRecord=" + newPos;

						buttons = childs[2].childElements()[0];
						//alert(buttons.nodeName);
						if (buttons && (p = buttons.href.split("&destination=")).length == 2)
								buttons.href = p[0] + "&destination=" + newPos;

					}

					function sortable_updatePasteButtons(oldPos, newPos) {
						var i = 0; var p = new Array; var href = "";
						var buttons = document.getElementsByClassName("sortablePaste");
						if (buttons[i].firstChild && buttons[i].firstChild.href.indexOf("&source="+escape(oldPos)) != -1) {
							for (i = 0; i < buttons.length; i++) {
								if (buttons[i].firstChild) {
									href = buttons[i].firstChild.href;
									if ((p = href.split("&source="+escape(oldPos))).length == 2) {
										buttons[i].firstChild.href = p[0] + "&source=" + escape(newPos) + p[1];
									}
								}
							}
						}
					}

					function sortable_update(el) {
						var node = el.firstChild;
						var i = 1;
						while (node != null) {
							if (node.className == "sortableItem") {
								//alert(node.id);
								if (sortable_currentItem && node.id == sortable_currentItem.id ) {
									var url = "index.php?'.$this->link_getParameters().'&ajaxPasteRecord=cut&source=" + sortable_currentItem.id + "&destination=" + el.id + (i-1);
									new Ajax.Request(url);
									sortable_updatePasteButtons(node.id, el.id + i);
									sortable_currentItem = false;
								}
								sortable_updateItemButtons(node, i, el.id)
								node.id = el.id + i;
								i++;
							}
							node	= node.nextSibling;
						}
					}

					function sortable_change(el) {
						sortable_currentItem=el;
					}
					';
					$containment = implode('","', $this->sortable_containers);
					foreach ($this->sortable_containers as $s) {
						$this->content .='
						Sortable.create(\''.$s.'\',{
							tag:"div",
							ghosting:false,
							format: /(.*)/,
							handle:"sortable_handle",
							dropOnEmpty:true,
							constraint:false,
							containment:["'.$containment.'"],
							onChange:sortable_change,
							onUpdate:sortable_update});';
					}
					$this->content .= '
						// -->
						</script>';
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
	 * Renders the display framework of a single sheet. Calls itself recursively
	 *
	 * @param	array		$contentTreeArr: DataStructure info array (the whole tree)
	 * @param	string		$languageKey: Language key for the display
	 * @param	string		$sheet: The sheet key of the sheet which should be rendered
	 * @param	array		$parentPointer: Flexform pointer to parent element
	 * @param	array		$parentDsMeta: Meta array from parent DS (passing information about parent containers localization mode)
	 * @return	string		HTML
	 * @access protected
	 * @see	render_framework_singleSheet()
	 */
	function render_framework_singleSheet($contentTreeArr, $languageKey, $sheet, $parentPointer=array(), $parentDsMeta=array()) {
		global $LANG, $TYPO3_CONF_VARS;

		$elementBelongsToCurrentPage = $contentTreeArr['el']['table'] == 'pages' || $contentTreeArr['el']['pid'] == $this->rootElementUid_pidForContent;

    $canEditPage = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'edit');
    $canEditContent = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'editcontent');

			// Prepare the record icon including a content sensitive menu link wrapped around it:
		$recordIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,$contentTreeArr['el']['icon'],'').' style="text-align: center; vertical-align: middle;" width="18" height="16" border="0" title="'.htmlspecialchars('['.$contentTreeArr['el']['table'].':'.$contentTreeArr['el']['uid'].']').'" alt="" />';

    $menuCommands = array();
		if ($GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'new')) {
			$menuCommands[] = 'new';
		}
		if ($canEditContent) {
    	$menuCommands[] = 'copy,cut,pasteinto,pasteafter,delete';
		}
		$titleBarLeftButtons = $this->translatorMode ? $recordIcon : (count($menuCommands) == 0 ? $recordIcon : $this->doc->wrapClickMenuOnIcon($recordIcon,$contentTreeArr['el']['table'], $contentTreeArr['el']['uid'], 1,'&amp;callingScriptId='.rawurlencode($this->doc->scriptID), implode(',', $menuCommands)));
		unset($menuCommands);






			// Prepare table specific settings:
		switch ($contentTreeArr['el']['table']) {

			case 'pages' :

				$titleBarLeftButtons .= $this->translatorMode || !$canEditPage ? '' : $this->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.htmlspecialchars($LANG->sL('LLL:EXT:lang/locallang_mod_web_list.xml:editPage')).'" alt="" style="text-align: center; vertical-align: middle; border:0;" />',$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']);
				$titleBarRightButtons = '';

				$addGetVars = ($this->currentLanguageUid?'&L='.$this->currentLanguageUid:'');
				$viewPageOnClick = 'onclick= "'.htmlspecialchars(t3lib_BEfunc::viewOnClick($contentTreeArr['el']['uid'], $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($contentTreeArr['el']['uid']),'','',$addGetVars)).'"';
				$viewPageIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.showPage',1).'" hspace="3" alt="" style="text-align: center; vertical-align: middle;" />';
				$titleBarLeftButtons .= '<a href="#" '.$viewPageOnClick.'>'.$viewPageIcon.'</a>';
			break;

			case 'tt_content' :

 				$elementTitlebarColor = ($elementBelongsToCurrentPage ? $this->doc->bgColor5 : $this->doc->bgColor6);
				$elementTitlebarStyle = 'background-color: '.$elementTitlebarColor;

				$languageUid = $contentTreeArr['el']['sys_language_uid'];

				if (!$this->translatorMode && $canEditContent)	{
						// Create CE specific buttons:
					$linkMakeLocal = !$elementBelongsToCurrentPage ? $this->link_makeLocal('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('templavoila').'mod1/makelocalcopy.gif','').' title="'.$LANG->getLL('makeLocal').'" border="0" alt="" />', $parentPointer) : '';
					$linkUnlink = $this->link_unlink('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','').' title="'.$LANG->getLL('unlinkRecord').'" border="0" alt="" />', $parentPointer, FALSE);
					if ($GLOBALS['BE_USER']->recordEditAccessInternals('tt_content', $contentTreeArr['previewData']['fullRow'])) {
						$linkEdit = ($elementBelongsToCurrentPage ? $this->link_edit('<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','').' title="'.$LANG->getLL ('editrecord').'" border="0" alt="" />',$contentTreeArr['el']['table'],$contentTreeArr['el']['uid']) : '');
					} else {
						$linkEdit = '';
					}
					$titleBarRightButtons = $linkEdit . $this->clipboardObj->element_getSelectButtons ($parentPointer) . $linkMakeLocal . $linkUnlink;
				} else {
					$titleBarRightButtons = '';
				}
			break;
		}

			// Prepare the language icon:
		$languageLabel = htmlspecialchars ($this->allAvailableLanguages[$contentTreeArr['el']['sys_language_uid']]['title']);
		$languageIcon = $this->allAvailableLanguages[$languageUid]['flagIcon'] ? '<img src="'.$this->allAvailableLanguages[$languageUid]['flagIcon'].'" title="'.$languageLabel.'" alt="'.$languageLabel.'" style="text-align: center; vertical-align: middle;" />' : ($languageLabel && $languageUid ? '['.$languageLabel.']' : '');

			// If there was a langauge icon and the language was not default or [all] and if that langauge is accessible for the user, then wrap the  flag with an edit link (to support the "Click the flag!" principle for translators)
		if ($languageIcon && $languageUid>0 && $GLOBALS['BE_USER']->checkLanguageAccess($languageUid) && $contentTreeArr['el']['table']==='tt_content')	{
			$languageIcon = $this->link_edit($languageIcon, 'tt_content', $contentTreeArr['el']['uid'], TRUE);
		}

			// Create warning messages if neccessary:
		$warnings = '';
		if ($this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']] > 1 && $this->rootElementLangParadigm !='free') {
			$warnings .= '<br/>'.$this->doc->icons(2).' <em>'.htmlspecialchars(sprintf($LANG->getLL('warning_elementusedmorethanonce',''), $this->global_tt_content_elementRegister[$contentTreeArr['el']['uid']], $contentTreeArr['el']['uid'])).'</em>';
		}

			// Displaying warning for container content (in default sheet - a limitation) elements if localization is enabled:
		$isContainerEl = count($contentTreeArr['sub']['sDEF']);
		if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning'] && $this->rootElementLangParadigm !='free' && $isContainerEl && $contentTreeArr['el']['table'] === 'tt_content' && $contentTreeArr['el']['CType'] === 'templavoila_pi1' && !$contentTreeArr['ds_meta']['langDisable'])	{
			if ($contentTreeArr['ds_meta']['langChildren'])	{
				if (!$this->modTSconfig['properties']['disableContainerElementLocalizationWarning_warningOnly']) {
					$warnings .= '<br/>'.$this->doc->icons(2).' <b>'.$LANG->getLL('warning_containerInheritance').'</b>';
				}
			} else {
				$warnings .= '<br/>'.$this->doc->icons(3).' <b>'.$LANG->getLL('warning_containerSeparate').'</b>';
			}
		}

			// Preview made:
		$previewContent = $this->render_previewData($contentTreeArr['previewData'], $contentTreeArr['el'], $contentTreeArr['ds_meta'], $languageKey, $sheet);

			// Wrap workspace notification colors:
		if ($contentTreeArr['el']['_ORIG_uid'])	{
			$previewContent = '<div class="ver-element">'.($previewContent ? $previewContent : '<em>[New version]</em>').'</div>';
		}

			// Finally assemble the table:
		$finalContent='';
		$finalContent.='
			<table cellpadding="0" cellspacing="0" style="width: 100%; border: 1px solid black; margin-bottom:5px;">
				<tbody>
				<tr style="'.$elementTitlebarStyle.';" class="sortable_handle">
					<td style="vertical-align:top;"> '.
						'<span class="nobr">'.
						$languageIcon.
						$titleBarLeftButtons.
						($elementBelongsToCurrentPage?'':'<em>').htmlspecialchars($contentTreeArr['el']['title']).($elementBelongsToCurrentPage ? '' : '</em>').
						'</span>'.
						$warnings.
					'</td>
					<td nowrap="nowrap" style="text-align:right; vertical-align:top;">'.
						$titleBarRightButtons.
					'</td>
				</tr>
				<tr>
					<td colspan="2">'.
						$this->render_framework_subElements($contentTreeArr, $languageKey, $sheet).
						$previewContent.
						$this->render_localizationInfoTable($contentTreeArr, $parentPointer, $parentDsMeta).
					'</td>
				</tr>
				</tbody>
			</table>
		';
		$canCreateNew = $GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'new');
		if (!$this->translatorMode && $canCreateNew)	{
				// "New" and "Paste" icon:
			$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
			$finalContent .= $this->link_new($newIcon, $parentPointer);

			$finalContent .= '<span class="sortablePaste">'.$this->clipboardObj->element_getPasteButtons ($parentPointer).'</span>';
			$finalContent = '<div class="sortableItem" id="'.$this->apiObj->flexform_getStringFromPointer($parentPointer).'">'.$finalContent.'</div>';
		}

		return $finalContent;
	}

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
				if (!$this->translatorMode && $canCreateNew)	{

						// "New" and "Paste" icon:
					$newIcon = '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_el.gif','').' style="text-align: center; vertical-align: middle;" vspace="5" hspace="1" border="0" title="'.$LANG->getLL ('createnewrecord').'" alt="" />';
					$cellContent .= $this->link_new($newIcon, $subElementPointer);
					$cellContent .= '<span class="sortablePaste">'.$this->clipboardObj->element_getPasteButtons ($subElementPointer).'</span>';
				}

					// Render the list of elements (and possibly call itself recursively if needed):
				if (is_array($fieldContent['el_list']))	 {
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
						}
					}
				}

					// Add cell content to registers:
				if ($flagRenderBeLayout==TRUE) {
					$beTemplateCell = '<table width="100%" class="beTemplateCell"><tr><td valign="top" style="background-color: '.$this->doc->bgColor4.'; padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td></tr><tr><td valign="top" style="padding: 5px;">'.$cellContent.'</td></tr></table>';
					$beTemplate = str_replace('###'.$fieldID.'###', $beTemplateCell, $beTemplate);
				} else {
							// Add cell content to registers:
					//$cellID=$this->apiObj->$elementContentTreeArr['el']['table'].':'.$fieldID.':'.$elementContentTreeArr['el']['uid'];
					$tmpArr=$subElementPointer;
					unset($tmpArr['position']);
					$cellID=$this->apiObj->flexform_getStringFromPointer($tmpArr);
					$headerCells[]='<td valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet][$lKey])).'%" style="background-color: '.$this->doc->bgColor4.'; padding-top:0; padding-bottom:0;">'.$LANG->sL($fieldContent['meta']['title'],1).'</td>';
					$cells[]='<td id="'.$cellID.'" valign="top" width="'.round(100/count($elementContentTreeArr['sub'][$sheet][$lKey])).'%" style="border: 1px dashed #000; padding: 5px 5px 5px 5px;">'.$cellContent.'</td>';
					if ($GLOBALS['BE_USER']->isPSet($this->calcPerms, 'pages', 'editcontent')) $this->sortable_containers[]=$cellID;
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
				<table  border="0" cellpadding="2" cellspacing="2" width="100%">
					<tbody>
					<tr>'.(count($headerCells) ? implode('', $headerCells) : '<td>&nbsp;</td>').'</tr>
					<tr>'.(count($cells) ? implode('', $cells) : '<td>&nbsp;</td>').'</tr>
					</tbody>
				</table>
		';
		}

		return $output;
	}

	function link_unlink($label, $unlinkPointer, $realDelete=FALSE)	{
		global $LANG;

		$unlinkPointerString = rawurlencode($this->apiObj->flexform_getStringFromPointer ($unlinkPointer));

		if ($realDelete)	{
			return '<a href="index.php?'.$this->link_getParameters().'&amp;deleteRecord='.$unlinkPointerString.'" onclick="'.htmlspecialchars('return confirm('.$LANG->JScharCode($LANG->getLL('deleteRecordMsg')).');').'">'.$label.'</a>';
		} else {
			return '<a href="javascript:'.htmlspecialchars('if (confirm('.$LANG->JScharCode($LANG->getLL('unlinkRecordMsg')).')) ').'sortable_unlinkRecord(\''.$unlinkPointerString.'\');">'.$label.'</a>';
		}
	}
}
?>