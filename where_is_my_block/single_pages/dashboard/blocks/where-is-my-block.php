<?php 
defined('C5_EXECUTE') or die(_('Access Denied.'));

// Type cast vars passed from controller
$arrBlockTypes = (array) $arrBlockTypes;
$arrItemsPerPage = (array) $arrItemsPerPage;

// Get links for any help blocks (ensure user can view them beforehand)
$objCache = Page::getByPath('/dashboard/system/optimization/clear_cache');
$objPerm = new Permissions($objCache);

if(!$objPerm->canRead()){
	$htmHelpLinks = '';
}else{
	$strClearCacheUrl = $objNh->getLinkToCollection($objCache, TRUE);
	
	$htmHelpLinks = '<span class="help-block">' . t('You may also want to <a href="' . $strClearCacheUrl . '">clear your cache</a> to ensure you have the most up-to-date results.') . '</span>';
}

// Generate option elements for block type select menu
$htmBtOpts = '<option value="">' . t('Choose a block type') . '</option>';

foreach($arrBlockTypes as $keyI => $arrBt){
	if(($keyI === 0) || $arrBlockTypes[($keyI - 1)]['category'] != $arrBt['category']){
		$strOptGroup = $objTh->specialchars(ucwords($arrBt['category']));
		
		if($keyI > 0) $htmBtOpts .= '</optgroup>';
		
		$htmBtOpts .= '<optgroup label="' . t($objTh->unhandle($strOptGroup)) . ' Blocks">';
	}
	
	$htmBtOpts .= '<option value="' . $arrBt['id'] . '">' . $objTh->specialchars(t($arrBt['name'])) . '</option>';
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
				<input type="hidden" name="sort_dir" value="asc">
				<input type="hidden" name="ccm_paging_p" value="1">
				
				<?php echo $interface->submit(t('Search'), 'wimb', 'left', 'secondary'); ?>
				
				<img src="/concrete/images/loader_intelligent_search.gif" width="43" height="11" id="ccm-wimb-loading" />
			</div>
		<!-- .row --></div>
	<!-- #wimb --></form>
<!-- .ccm-pane-options --></div>

<div class="ccm-pane-body">
	<table cellspacing="0" cellpadding="0" border="0" class="ccm-results-list" id="ccm-where-is-my-block">
		<thead>
			<tr>
				<th>Page Name</th>
				<th>Page Path</th>
				<th>Instances</th>
			</tr>
		</thead>
		<tbody>
			<tr class="ccm-list-record">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
	</table>
	
	<hr />
	
	<span class="help-block"><?php echo t('Some pages/blocks may be omitted due to your current viewing permissions.'); ?></span>
	<span class="help-block"><?php echo t('System pages (e.g. <em>Login, Error 404, dashboard pages, etc.</em>) are not searched.'); ?></span>
	
	<?php echo $htmHelpLinks; ?>
	
<!-- .ccm-pane-body --></div>
	
<div class="ccm-pane-footer"></div>

<script type="text/javascript">
jQuery(document).ready(function($){
	var oWimbForm = new Wimb.SearchForm();
});
</script>

<?php
// End pane
echo $objDh->getDashboardPaneFooterWrapper(false);
?>