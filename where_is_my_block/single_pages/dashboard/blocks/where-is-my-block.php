<?php  
defined('C5_EXECUTE') or die(_('Access Denied.'));

// Type cast form vars passed from controller
$arrBlockTypes = array_values((array) $arrBlockTypes);
$arrItemsPerPage = (array) $arrItemsPerPage;

// Set default form options
$arrFormOpts = array(
	'btid' => '',
	'ipp' => 10,
	'sort_by' => 'page_name',
	'sort_dir' => 'asc',
	'ccm_paging_p' => 1
);

// Override default form options if saved options exist
if(isset($_COOKIE['wimb_form_options']) && is_string($_COOKIE['wimb_form_options'])){
	$arrFormOpts = unserialize($_COOKIE['wimb_form_options']);
}

// Get any help blocks (ensure user can view any links beforehand)
$strViewPermText = t('Some pages may be omitted due to your current viewing permissions.');

$objCache = Page::getByPath('/dashboard/system/optimization/clear_cache');
$objPerm = new Permissions($objCache);

if(!$objPerm->canRead()){
	$htmClearCacheText = '';
}else{
	$strClearCacheUrl = $objNh->getLinkToCollection($objCache, TRUE);	
	$htmClearCacheText = t('You may also want to <a href="%s">clear your cache</a> to ensure you have the most up-to-date results.', $strClearCacheUrl);
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
		
		$htmBtOpts .= '<optgroup label="' . t('%s Blocks', t($objTh->unhandle($strOptGroup))) . '">';
	}
	
	$strSelected = $arrFormOpts['btid'] == $arrBt['id'] ? ' selected' : '';

	$htmBtOpts .= '<option value="' . $arrBt['id'] . '"' . $strSelected . '>' . $objTh->specialchars(t($arrBt['name'])) . '</option>';
}

// Generate option elements for items per page select menu 
$htmIppOpts = '';

foreach($arrItemsPerPage as $intPerPage){
	$strSelected = $arrFormOpts['ipp'] == $intPerPage ? ' selected' : '';

	$htmIppOpts .= '<option value="' . $intPerPage . '"' . $strSelected . '>' . $intPerPage . '</option>';
}

// Begin pane
$htmHelpToolTip = $objPkg->getPackageDescription();
$htmHelpToolTip .= '<br /><br />' . $strViewPermText;
$htmHelpToolTip .= strlen($htmClearCacheText) > 0 ? ('<br /><br />' . $htmClearCacheText) : '';

echo $objDh->getDashboardPaneHeaderWrapper($objPkg->getPackageName(), $htmHelpToolTip, 'span12', FALSE);
?>

<div class="ccm-pane-options clearfix">
	<form id="wimb" class="form-horizontal" method="get" action="<?php echo $objNh->getLinkToCollection($this->c, TRUE); ?>">
		<div class="row ccm-pane-options-permanent-search">
			<div class="span4">
				<select name="btid" class="ccm-input-select"><?php echo $htmBtOpts; ?></select>
			</div>
			
			<div class="span3">
				<label for="ipp" class="control-label"><?php echo t('# Per Page'); ?></label>
				
				<div class="controls">
					<select name="ipp" class="ccm-input-select"><?php echo $htmIppOpts; ?></select>
				</div>
			</div>
			
			<div class="span4">

				<?php
				$this->controller->token->output('wimb_page_block_search');

				echo $objFh->hidden('sort_by', $arrFormOpts['sort_by']);
				echo $objFh->hidden('sort_dir', $arrFormOpts['sort_dir']);
				echo $objFh->hidden('ccm_paging_p', $arrFormOpts['ccm_paging_p'] ? $arrFormOpts['ccm_paging_p'] : 1);
				echo $objFh->hidden('refresh', 1);

				echo $interface->submit(t('Search'), 'wimb', 'left', 'ccm-input-submit secondary');
				?>
				
			</div>
		<!-- .row --></div>
	<!-- #wimb --></form>
<!-- .ccm-pane-options --></div>

<div class="ccm-pane-body">
	<hr />

	<span class="help-block"><?php echo $strViewPermText ?></span>
	
	<span class="help-block"><?php echo $htmClearCacheText; ?></span>
<!-- .ccm-pane-body --></div>
	
<div class="ccm-pane-footer"></div>

<script type="text/javascript">
jQuery(document).ready(function($){
	var WimbForm = new WhereIsMyBlock.Form();
	WimbForm.init();
});
</script>

<?php 
// End pane
echo $objDh->getDashboardPaneFooterWrapper(FALSE);
?>