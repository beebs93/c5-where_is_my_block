<?php 
defined('C5_EXECUTE') or die(_('Access Denied.'));

class DashboardBlocksWhereIsMyBlockController extends DashboardBaseController{
	protected $arrBlockTypes = array();
	protected $arrItemsPerPage = array(10, 25, 50, 100, 500);
	protected $arrSortableCols = array('page_name', 'page_path', 'instances');
	public $helpers = array('concrete/dashboard', 'form', 'navigation', 'text');
	
	
	/**
	 * Get a list of all installed block types and extract the pertinent
	 * information from each block type object
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.1.1
	 */
	protected function setAllowedBlockTypes(){
		// Get a list of all installed block types and extract the pertinent
		// information from each block type object
		$arrAllBlockTypes = (array) BlockTypeList::getInstalledList();
		
		foreach($arrAllBlockTypes as $objBt){
			$arrBtInfo = array(
				'id' => (int) $objBt->btID,
				'handle' => (string) $objBt->btHandle,
				'name' => (string) $objBt->btName,
				'category' => $objBt->isCoreBlockType() ? 'core' : 'third_party'
			);
			
			$this->arrBlockTypes[$arrBtInfo['id']] = $arrBtInfo;
		}
		
		// Sort by category then by handle
		uasort($this->arrBlockTypes, create_function('$a, $b', '
			if($a["category"] == $b["category"]){
				return strnatcmp($a["handle"], $b["handle"]);
			}
			return strnatcmp($a["category"], $b["category"]);
		'));
	}


	/**
	 * Determines if the list of allowed block types has been generated
	 * 
	 * @return boolean
	 *
	 * @author Brad Beebe
	 * @since v0.9.1.1
	 */
	protected function isAllowedBlockTypesSet(){
		return count($this->arrAllBlockTypes) && count($this->arrAllowedBtIds);
	}
	
	
	/**
	 * Implementation of on_start()
	 * 
	 * @return void
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function on_start(){
		$objHh = Loader::helper('html');
		
		$arrJsConstants = array(
			'URL_TOOL_PAGE_BLOCK_SEARCH' => Loader::helper('concrete/urls')->getToolsURL('page_block_list.php', 'where_is_my_block'),
			'TEXT_GENERAL_ERROR' => t('There was an error with your request'),
			'TEXT_AJAX_ERROR' => t('An Ajax error occured: '),
			'TEXT_TABLE_COLUMNS' => array(
				'page_name' => t('Page Name'),
				'page_path' => t('Page Path'),
				'instances' => t('Instances')
			)
		);

		$this->addHeaderItem($objHh->css('wimb.css', 'where_is_my_block'));
		$this->addFooterItem($objHh->javascript('wimb.min.js', 'where_is_my_block'));
		$this->addFooterItem('
		<script type="text/javascript">
		jQuery.extend(WhereIsMyBlock, ' . json_encode($arrJsConstants) . ');

		jQuery(document).ready(function(){
			var WimbForm = new WhereIsMyBlock.Form();
		});
		</script>');

		parent::on_start();
	}
	
	
	/**
	 * Single page view
	 * 
	 * @return void
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function view(){
		if(!$this->isAllowedBlockTypesSet()){
			$this->setAllowedBlockTypes();
		}

		// Add any core helpers, models, etc. in the view scope
		$this->set('objDh', $this->helperObjects['concrete_dashboard']);
		$this->set('objFh', $this->helperObjects['form']);
		$this->set('objNh', $this->helperObjects['navigation']);
		$this->set('objTh', $this->helperObjects['text']);
		$this->set('objPkg', Loader::package('where_is_my_block'));
		
		// Add any additional vars in the view scope
		$this->set('arrJsConstants', $arrJsConstants);
		$this->set('arrBlockTypes', $this->arrBlockTypes);
		$this->set('arrItemsPerPage', $this->arrItemsPerPage);
	}
	
	
	/**
	 * Checks if a block type ID is in the list of allowed block type IDs that can be searched for
	 * 
	 * @param mixed $btId - A block type ID
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function isAllowedBlockTypeId($btId){
		if(!$this->isAllowedBlockTypesSet()){
			$this->setAllowedBlockTypes();
		}

		return is_numeric($btId) && array_key_exists((int) $btId, $this->arrBlockTypes);
	}
	
	
	/**
	 * Checks if a string is one of the results table columns that data can be sorted by
	 * 
	 * @param string $strColumn - A column name
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function isValidSortableCol($strColumn){
		return in_array((string) $strColumn, $this->arrSortableCols);
	}
	
	
	/**
	 * Checks if a value is one of the items per page options to display results
	 * 
	 * @param mixed $ipp - A items per page value
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function isValidItemsPerPage($ipp){
		return (is_numeric($ipp)) && in_array((int) $ipp, $this->arrItemsPerPage);
	}
}