<?php
defined('C5_EXECUTE') or die(_('Access Denied.'));

Loader::model('page_list');
$objJh = Loader::helper('json');
$objController = Loader::controller('/dashboard/blocks/where-is-my-block');
$objUser = new User();

// Extract search parameters
$intSearchBtId = (int) $_GET['btid'];

$intSearchIpp = (int) $_GET['ipp'];
if(!$objController->isValidItemsPerPage($intSearchIpp)) $intSearchIpp = 10;

$strSearchSort = strtolower((string) $_GET['sort_by']);
if(!$objController->isValidSortableCol($strSearchSort)) $strSearchSort = 'page_name';

$strSearchDir = strtolower((string) $_GET['sort_dir']);
if($strSearchDir != 'desc') $strSearchDir = 'asc';

$blnRefresh = isset($_GET['refresh']) ? (bool) $_GET['refresh'] : FALSE;

// Check for a valid block type ID
$htmError = FALSE;
if(!is_numeric($intSearchBtId) || $intSearchBtId < 0){
	$htmError = $objController->getAlert(t('...Really?'), 'error');
}elseif($intSearchBtId == 0){
	$htmError = $objController->getAlert(t('You need to select a block type to search for'), 'warning');
}elseif(!$objController->isAllowedBlockTypeId($intSearchBtId)){
	$htmError = $objController->getAlert(t('You cannot search for that block type'), 'error');
}

// Return any errors
if($htmError){
	$objResp = new stdClass();
	$objResp->status = 'error';
	$objResp->alert = $htmError;
	$objResp->message = t('There was an error with your request');
	
	header('Content-type: application/json');
	echo $objJh->encode($objResp);
	exit;
}

// Check for cached data
if((!defined('ENABLE_CACHE')) || !ENABLE_CACHE) Cache::enableCache();
$arrPageBlockInfo = ($cachePgBlkInfo = Cache::get('wimb', 'pageBlockInfo_' . $objUser->uID, FALSE, TRUE)) ? $cachePgBlkInfo : array();
$arrPageIds = ($cachePageIds = Cache::get('wimb', 'pageIds_' . $objUser->uID, FALSE)) ? $cachePageIds : array();
if((!defined('ENABLE_CACHE')) || !ENABLE_CACHE) Cache::disableCache();

// Refresh cache if needed
if(count($arrPageBlockInfo) == 0 || count($arrPageIds) == 0 || $blnRefresh === TRUE){
	// Get a list of all non-system, non-aliased pages viewable by the current user
	$objHome = Page::getByID(HOME_CID);
	$strHomePath = (string) $objHome->getCollectionPath();

	$objPl = new PageList();
	$objPl->filterByPath($strHomePath, TRUE);
	$objPl->ignoreAliases();
	$arrAllowedPages = (array) $objPl->get();

	$objPerm = new Permissions($objHome);
	if($objPerm->canRead()) array_unshift($arrAllowedPages, $objHome);

	// For any page that has at least one of the block type we are searching for, get
	// the page name, path and total number of instances while also recording the page ID
	$arrPageBlockInfo = array();
	$arrPageIds = array();
	foreach($arrAllowedPages as $objPage){
		if((!is_object($objPage)) || !$objPage instanceof Page || $objPage->error) continue;
		
		$intPageId = $objPage->getCollectionID();
		$strName = $objPage->getCollectionName();
		$strPath = trim($objPage->getCollectionPath());
		if(strlen($strPath) == 0) $strPath = '/';
		
		$arrPageBlocks = (array) $objPage->getBlocks(FALSE);
		
		foreach($arrPageBlocks as $objBlock){
			if((!$objBlock instanceof Block) || $objBlock->btID != $intSearchBtId) continue;
			
			$objBlkPerm = new Permissions($objBlock);
			if(!$objBlkPerm->canRead()) continue;
			
			if(is_array($arrPageBlockInfo[$strPath])){
				$arrPageBlockInfo[$strPath]['instances']++;

				continue;
			}

			$arrPageBlockInfo[$strPath] = array(
				'page_name' => $strName,
				'page_path' => $strPath,
				'instances' => 1
			);
			
			$arrPageIds[] = $intPageId;
		}
	}

	// Return error message if no pages found that contain the specific block type
	if(count($arrPageIds) == 0){
		$objResp = new stdClass();
		$objResp->status = 'error';
		$objResp->alert = '';
		$objResp->message = t('No pages contain that block type');
		
		header('Content-type: application/json');
		echo $objJh->encode($objResp);
		exit;
	}

	// Cache the results for future sorting/pagination
	if((!defined('ENABLE_CACHE')) || !ENABLE_CACHE) Cache::enableCache();
	Cache::set('wimb', 'pageBlockInfo_' . $objUser->uID, $arrPageBlockInfo, (time() + 600));
	Cache::set('wimb', 'pageIds_' . $objUser->uID, $arrPageIds, (time() + 600));	
	if((!defined('ENABLE_CACHE')) || !ENABLE_CACHE) Cache::disableCache();
}

// Convert the list of page IDs into a query string
$strFilter = '(p1.cID IN(' . implode(',', $arrPageIds) . '))';

// Get a paginated list of pages using the page IDs from the first PageList request
// so we can use its built-in paginator to easily extract any pagination information
$objPl = new PageList();
$objPl->filter(FALSE, $strFilter);
$objPl->setItemsPerPage($intSearchIpp);	
(array) $objPl->getPage();

// Apply sorting
switch($strSearchSort){
	case 'page_name':
		$strFirst = $strSearchDir == 'asc' ? '$a' : '$b';
		$strSecond = $strFirst == '$a' ? '$b' : '$a';
		
		usort($arrPageBlockInfo, create_function('$a, $b', '
			return strnatcmp(strtolower(' . $strFirst . '["page_name"]), strtolower(' . $strSecond . '["page_name"]));
		'));		
	break;
	
	case 'page_path':
		$strFirst = $strSearchDir == 'asc' ? '$a' : '$b';
		$strSecond = $strFirst == '$a' ? '$b' : '$a';
		
		usort($arrPageBlockInfo, create_function('$a, $b', '
			$strRgx = "/[^a-zA-Z0-9]/";
			return strnatcmp(preg_replace($strRgx, "", ' . $strFirst . '["page_path"]), preg_replace($strRgx, "", ' . $strSecond . '["page_path"]));
		'));		
	break;
	
	case 'instances':
		$strOperator = $strSearchDir == 'asc' ? '>' : '<';
		
		usort($arrPageBlockInfo, create_function('$a, $b', '
			return $a["instances"] ' . $strOperator . ' $b["instances"];
		'));		
	break;
}

// If the results are paginated we get the pagination HTML and slice our custom results array
// to reflect the current offset and items per page parameter
if($objPl->getSummary()->pages > 1){
	$objPgn = $objPl->getPagination();
	
	$arrPageBlockInfo = array_slice($arrPageBlockInfo, $objPgn->result_offset, $objPgn->page_size);
	
	$htmPgn = (string) $objPl->displayPagingV2(FALSE, TRUE);
	$strPgnInfo = t('Viewing ' . $objPgn->result_lower . ' to ' . $objPgn->result_upper . ' (' . $objPgn->result_count . ' Total)');
}else{
	$intCurrentRows = count($arrPageBlockInfo);
	
	$htmPgn = '';
	$strPgnInfo = t('Viewing 1 to ' . $intCurrentRows . ' (' . $intCurrentRows . ' Total)');
}

$objResp = new stdClass();
$objResp->status = 'success';
$objResp->alert = '';
$objResp->message = '';
$objResp->response = new stdClass();
$objResp->response->tblData = $arrPageBlockInfo;
$objResp->response->pgnHtml = $htmPgn;
$objResp->response->pgnInfo = $strPgnInfo;

header('Content-type: application/json');
echo $objJh->encode($objResp);
exit;