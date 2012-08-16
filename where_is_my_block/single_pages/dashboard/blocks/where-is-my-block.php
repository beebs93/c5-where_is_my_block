<?php 
defined('C5_EXECUTE') or die(_('Access Denied.'));

// Type cast form vars passed from controller
$arrBlockTypes = (array) $arrBlockTypes;
$arrItemsPerPage = (array) $arrItemsPerPage;

// Get any help blocks (ensure user can view any links beforehand)
$strViewPermText = t('Some pages/blocks may be omitted due to your current viewing permissions.');;

$objCache = Page::getByPath('/dashboard/system/optimization/clear_cache');
$objPerm = new Permissions($objCache);

if(!$objPerm->canRead()){
	$htmClearCacheText = '';
}else{
	$strClearCacheUrl = $objNh->getLinkToCollection($objCache, TRUE);	
	$htmClearCacheText = t('You may also want to <a href="' . $strClearCacheUrl . '">clear your cache</a> to ensure you have the most up-to-date results.');
}

// Generate option elements for block type select menu
$htmBtOpts = '<option value="">' . t('Choose a block type') . '</option>';

foreach($arrBlockTypes as $keyI => $arrBt){
	// Break options list into option groups based on block type category
	if(($keyI === 0) || $arrBlockTypes[($keyI - 1)]['category'] != $arrBt['category']){
		$strOptGroup = $objTh->specialchars(ucwords($arrBt['category']));
		
		if($keyI > 0){
			$htmBtOpts .= '</optgroup>';
		}
		
		$htmBtOpts .= '<optgroup label="' . t($objTh->unhandle($strOptGroup) . ' Blocks') . '">';
	}
	
	$htmBtOpts .= '<option value="' . $arrBt['id'] . '">' . $objTh->specialchars(t($arrBt['name'])) . '</option>';
}

// Generate option elements for items per page select menu 
$htmIppOpts = '';

foreach($arrItemsPerPage as $intPerPage){
	$htmIppOpts .= '<option value="' . $intPerPage . '">' . $intPerPage . '</option>';
}

// Begin pane
$htmHelpToolTip = $objPkg->getPackageDescription() . '<br /><br />';
$htmHelpToolTip .= $strViewPermText . '<br /><br />';
$htmHelpToolTip .= $htmClearCacheText;

echo $objDh->getDashboardPaneHeaderWrapper($objPkg->getPackageName(), $htmHelpToolTip, 'span16', FALSE);
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

				<?php $this->controller->token->output('wimb_page_block_search'); ?>

				<input type="hidden" name="sort_by" value="page_name" />
				<input type="hidden" name="sort_dir" value="asc" />
				<input type="hidden" name="ccm_paging_p" value="1" />
				<input type="hidden" name="refresh" value="1" />
				
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
	
	<span class="help-block"><?php echo $strViewPermText ?></span>
	<span class="help-block"><?php echo $htmClearCacheText; ?></span>
<!-- .ccm-pane-body --></div>
	
<div class="ccm-pane-footer"></div>

<script type="text/javascript">
jQuery(document).ready(function($){
	var oWimbForm = new WhereIsMyBlock.Form();
	oWimbForm.init();
});
</script>

<?php
// End pane
echo $objDh->getDashboardPaneFooterWrapper(FALSE);
?>