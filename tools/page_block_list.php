<?php
defined('C5_EXECUTE') or die('Access Denied.');
Loader::model('page_list');
$objJh = Loader::helper('json');
$objNh = Loader::helper('navigation');
$objController = Loader::controller('/dashboard/blocks/where-is-my-block');

// Extract search parameters
$intSearchBtId = (int) $_GET['btid'];

$intSearchIpp = (int) $_GET['ipp'];
if(!$objController->isValidItemsPerPage($intSearchIpp)) $intSearchIpp = 10;

$strSearchSort = (string) $_GET['sort_by'];
if(!$objController->isValidSortableCol($strSearchSort)) $strSearchSort = 'page_name';

$strSearchDir = (string) $_GET['sort_dir'];
if($strSearchDir != 'asc') $strSearchDir = 'desc';

// Check for a valid block type ID
$htmError = FALSE;
if(!is_numeric($intSearchBtId) || $intSearchBtId < 0){
	$htmError = $objController->getAlert('...Really?', 'error');
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

// Get home page path
$objHome = Page::getByID(HOME_CID);
$strHomePath = strlen($objHome->cPath) > 0 ? $objHome->cPath : '';

// Get a list of all the non-aliased pages that the current user has permission to view
$objPl = new PageList();
$objPl->filterByPath($strHomePath, TRUE);
$objPl->setupPermissions();
$objPl->ignoreAliases();
$arrPages = (array) $objPl->get();

// Add home Page object to list of pages
$arrPages[] = $objHome;

// Generate list of page IDs that contain the block type we are searching for
// NOTE: If there is a more elegant way to do this I am all ears
$arrPageIds = array();
foreach($arrPages as $objPage){
	if((!$objPage instanceof Page) || $objPage->error) continue;
	
	$intPageId = $objPage->getCollectionID();
	
	$arrPageBlocks = (array) $objPage->getBlocks(FALSE);
	
	foreach($arrPageBlocks as $objBlock){
		if((!$objBlock instanceof Block) || $objBlock->btID != $intSearchBtId) continue;
		
		$arrPageIds[] = $intPageId;
		
		break;
	}
}

// If there are any instances of the block type in the pages
// from the first PageList request
if(count($arrPageIds) > 0){
	// Convert the list of page IDs into a query string for the next PageList request
	$strFilter = '(p1.cID IN(' . implode(',', $arrPageIds) . '))';

	// Get a paginated list of pages with the block type we are searching for			
	$objPl = new PageList();
	$objPl->filter(FALSE, $strFilter);
	$objPl->setItemsPerPage($intSearchIpp);
				
	if($strSearchSort == 'page_name') $objPl->sortBy('cvName', $strSearchDir);
	
	$arrPages = (array) $objPl->getPage();

	// Get the page name, path and number of instances for the block type on each page
	// we received from the first PageList request
	$arrPageBlockInfo = array();
	foreach($arrPages as $objPage){
		if((!$objPage instanceof Page) || $objPage->error) continue;

		$strName = $objPage->getCollectionName();
		$strPath = trim($objPage->getCollectionPath());
		if(strlen($strPath) == 0) $strPath = '/';

		$arrPageBlocks = (array) $objPage->getBlocks(FALSE);

		foreach($arrPageBlocks as $objBlock){
			if((!$objBlock instanceof Block) || $objBlock->btID != $intSearchBtId) continue;

			if(is_array($arrPageBlockInfo[$strPath])){
				$arrPageBlockInfo[$strPath]['instances']++;

				continue;
			}

			$arrPageBlockInfo[$strPath] = array(
				'page_name' => $strName,
				'page_path' => $objNh->getLinkToCollection($objPage, TRUE),
				'instances' => 1
			);
		}
	}
	
	
	// Sort by instances (if requested)
	if($strSearchSort == 'instances'){
		$strOperator = $strSearchDir == 'asc' ? '>' : '<';
		
		usort($arrPageBlockInfo, create_function('$a, $b', '
			return $a["instances"] ' . $strOperator . ' $b["instances"];
		'));
	// Sort by path (if requested)
	}elseif($strSearchSort == 'page_path'){
		$strFirst = $strSearchDir == 'asc' ? '$a' : '$b';
		$strSecond = $strFirst == '$a' ? '$b' : '$a';
		
		usort($arrPageBlockInfo, create_function('$a, $b', '
			$strRgx = "/[^a-zA-Z0-9]/";
			return strnatcmp(preg_replace($strRgx, "", ' . $strFirst . '["page_path"]), preg_replace($strRgx, "", ' . $strSecond . '["page_path"]));
		'));
	}
	
	// Re-index array keys
	$arrPageBlockInfo = array_values($arrPageBlockInfo);
	
	// Get pagination HTML
	$htmPgn = (string) $objPl->displayPagingV2(FALSE, TRUE);

	$objResp = new stdClass();
	$objResp->status = 'success';
	$objResp->alert = '';
	$objResp->message = '';
	$objResp->response = $arrPageBlockInfo;
	$objResp->pagination = $htmPgn;
	
	header('Content-type: application/json');
	echo $objJh->encode($objResp);
	exit;	
}else{
	$objResp = new stdClass();
	$objResp->status = 'error';
	$objResp->alert = '';
	$objResp->message = t('No pages contain that block type');
	
	header('Content-type: application/json');
	echo $objJh->encode($objResp);
	exit;
}