<?php 
defined('C5_EXECUTE') or die('Access Denied.');

// Type cast vars passed from controller
$arrBlockTypes = (array) $arrBlockTypes;
$arrItemsPerPage = (array) $arrItemsPerPage;

// Get links for any help blocks
$strClearCacheUrl = $objNh->getLinkToCollection(Page::getByPath('/dashboard/system/optimization/clear_cache'), TRUE);

// Generate option elements for block type select menu
$htmBtOpts = '<option value="">' . t('Choose a block type') . '</option>';
	
foreach($arrBlockTypes as $keyI => $arrBt){
	if(($keyI === 0) || $arrBlockTypes[($keyI - 1)]['category'] != $arrBt['category']){
		$strOptGroup = $objTh->specialchars(ucwords($arrBt['category']));
		
		if($keyI > 0) $htmBtOpts .= '</optgroup>';
		
		$htmBtOpts .= '<optgroup label="' . t($objTh->unhandle($strOptGroup)) . ' Blocks">';
	}
	
	$htmBtOpts .= '<option value="' . $arrBt['id'] . '">' . t($objTh->specialchars($arrBt['name'])) . '</option>';
}

// Generate option elements for items per page select menu 
$htmIppOpts = '';

foreach($arrItemsPerPage as $intPerPage){
	$htmIppOpts .= '<option value="' . $intPerPage . '">' . $intPerPage . '</option>';
}

// Begin pane
echo $objDh->getDashboardPaneHeaderWrapper($objPkg->getPackageName(), $objPkg->getPackageDescription(), 'span16', false);
?>

<div class="ccm-pane-options clearfix">
	<form id="wimb" method="get" action="<?php echo $objNh->getLinkToCollection($this->c, TRUE); ?>">
		<div class="row ccm-pane-options-permanent-search">
			<div class="span4">
				<select name="btid"><?php echo $htmBtOpts; ?></select>
			</div>
			
			<div class="span3">
				<label for="ipp"><?php echo t('# Per Page'); ?></label>
				<select name="ipp"><?php echo $htmIppOpts; ?></select>
			</div>
			
			<div class="span4">
				<input type="hidden" name="sort_by" value="page_name">
				<input type="hidden" name="sort_dir" value="desc">
				<input type="hidden" name="ccm_paging_p" value="1">
				
				<?php echo $interface->submit(t('Search'), 'wimb', 'left', 'secondary'); ?>
				
				<img src="/concrete/images/loader_intelligent_search.gif" width="43" height="11" id="ccm-wimb-loading" />
			</div>
		<!-- .row --></div>
	<!-- #wimb --></form>
<!-- .ccm-pane-options --></div>

<div class="ccm-pane-body">
	<span class="help-block"><?php echo t('Some pages may be omitted due to your current viewing permissions.'); ?></span>
	<span class="help-block"><?php echo t('System pages (e.g. <em>Login, Error 404, dashboard pages, etc.</em>) are not searched.'); ?></span>
	<span class="help-block"><?php echo t('You may also want to <a href="' . $strClearCacheUrl . '">clear your cache</a> to ensure you have the most up-to-date results.'); ?></span>
    
    <div id="bodyOverlay"></div>
<!-- .ccm-pane-body --></div>
	
<div class="ccm-pane-footer"></div>


<?php
// End pane
echo $objDh->getDashboardPaneFooterWrapper(false);
?>