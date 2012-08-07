<?php
defined('C5_EXECUTE') or die(_('Access Denied.'));

Loader::model('page_list');
$objJh = Loader::helper('json');
$objController = Loader::controller('/dashboard/blocks/where-is-my-block');

// Extract search parameters
$intSearchBtId = (int) $_GET['btid'];

$intSearchIpp = (int) $_GET['ipp'];
if(!$objController->isValidItemsPerPage($intSearchIpp)) $intSearchIpp = 10;

$strSearchSort = strtolower((string) $_GET['sort_by']);
if(!$objController->isValidSortableCol($strSearchSort)) $strSearchSort = 'page_name';

$strSearchDir = strtolower((string) $_GET['sort_dir']);
if($strSearchDir != 'desc') $strSearchDir = 'asc';

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

// Generate list of page IDs that contain the block type we are searching for
// NOTE: If there is a more elegant way to do this I am all ears
$arrPageIds = array();
foreach($objController->arrAllowedPageObjs as $objPage){
	if((!is_object($objPage)) || !$objPage instanceof Page || $objPage->error) continue;
	
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
	
	// Clone PageList so we can get entire non-paginated result set
	$objPlClone = clone $objPl;
	
	// Get paginated results
	$arrPages = (array) $objPl->getPage();	
	// Get non-paginated results (to allow for accurate custom sorting)
	$arrAllPages = (array) $objPlClone->get();

	// Get the page name, path and number of instances for the block type on each page
	// we received from the first PageList request
	$arrPageBlockInfo = array();
	foreach($arrAllPages as $objPage){
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
				'page_path' => $strPath,
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
	$intCurrentRows = count($arrPageBlockInfo);
	
	// If the results are paginated we get the pagination HTML and slice our custom results array
	// to reflect the current offset and items per page parameter
	if($objPl->getSummary()->pages > 1){
		$objPgn = $objPl->getPagination();
		
		$arrPageBlockInfo = array_slice($arrPageBlockInfo, $objPgn->result_offset, $objPgn->page_size);
		
		$htmPgn = (string) $objPl->displayPagingV2(FALSE, TRUE);
		$strPgnInfo = t('Viewing ' . $objPgn->result_lower . ' to ' . $objPgn->result_upper . ' (' . $objPgn->result_count . ' Total)');
	}else{
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
}else{
	$objResp = new stdClass();
	$objResp->status = 'error';
	$objResp->alert = '';
	$objResp->message = t('No pages contain that block type');
	
	header('Content-type: application/json');
	echo $objJh->encode($objResp);
	exit;
}