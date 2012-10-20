<?php
defined('C5_EXECUTE') or die(_('Access Denied.'));

class DashboardBlocksWhereIsMyBlockController extends DashboardBaseController{
	protected $arrBlockTypes = array();
	protected $arrAllowedBtIds = array();
	protected $arrItemsPerPage = array(10, 25, 50, 100, 500);
	protected $arrSortableCols = array('page_name', 'page_path', 'instances');
	public $helpers = array('concrete/dashboard', 'form', 'navigation', 'text');
	
		
	/**
	 * Constructor
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function __construct(){
		parent::__construct();
		
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
			
			$this->arrBlockTypes[] = $arrBtInfo;
			// Keep a separate array that records the valid block type IDs
			// since running usort on arrays resets their keys
			$this->arrAllowedBtIds[$arrBtInfo['id']] = TRUE;
		}
		
		// Sort by category then by handle
		usort($this->arrBlockTypes, create_function('$a, $b', '
			if($a["category"] == $b["category"]){
				return strnatcmp($a["handle"], $b["handle"]);
			}
			return strnatcmp($a["category"], $b["category"]);
		'));
	}
	
	
	/**
	 * Implementation of on_start()
	 * 
	 * @return void
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function on_start(){
		$objUh = Loader::helper('concrete/urls');
		$objHh = Loader::helper('html');
		
		$strJs = '
		<script type="text/javascript">
		var WhereIsMyBlock = WhereIsMyBlock || {};
		WhereIsMyBlock.URL_TOOL_PAGE_BLOCK_SEARCH = "' . $objUh->getToolsURL('page_block_list.php', 'where_is_my_block') . '";
		</script>';
		
		$this->addHeaderItem($objHh->css('wimb.css', 'where_is_my_block'));
		$this->addHeaderItem($strJs);
		$this->addHeaderItem($objHh->javascript('wimb.min.js', 'where_is_my_block'));
		
		parent::on_start();
	}
	
	
	/**
	 * Single page view
	 * 
	 * @return void
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function view(){
		// Add any core helpers, models, etc. in the view scope
		$this->set('objDh', $this->helperObjects['concrete_dashboard']);
		$this->set('objFh', $this->helperObjects['form']);
		$this->set('objNh', $this->helperObjects['navigation']);
		$this->set('objTh', $this->helperObjects['text']);
		$this->set('objPkg', Loader::package('where_is_my_block'));
		
		// Add any form vars in the view scope
		$this->set('arrBlockTypes', $this->arrBlockTypes);
		$this->set('arrItemsPerPage', $this->arrItemsPerPage);
	}


	/**
	 * Returns the HTML of a Twitter Bootstrap'd alert message
	 * 
	 * @param string $strMsg - Alert message
	 * @param string $strSeverity - Severity level
	 * @return string
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */	
	public function getAlert($strMsg, $strSeverity = 'info'){
		$strMsg = $this->helperObjects['text']->specialchars((string) $strMsg);
		
		$tmpAlert = '
		<div class="ccm-ui" id="ccm-dashboard-result-message">
			<div class="row">
				<div class="span16">
					<div class="alert-message %2$s">%1$s</div>
				</div>
			</div>
		</div>';
		
		return preg_replace('/[\r\n\t]/', '', sprintf($tmpAlert, $strMsg, (string) $strSeverity));
	}	
	
	
	/**
	 * Checks if a block type ID is in the list of allowed block type IDs that can be searched for
	 * 
	 * @param mixed $btId - A block type ID
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function isAllowedBlockTypeId($btId){
		return ((is_numeric($btId)) && $this->arrAllowedBtIds[(int) $btId] === TRUE);
	}
	
	
	/**
	 * Checks if a string is one of the results table columns that data can be sorted by
	 * 
	 * @param string $strColumn - A column name
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
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
	 * @since July 12, 2012
	 */
	public function isValidItemsPerPage($ipp){
		return (is_numeric($ipp)) && in_array((int) $ipp, $this->arrItemsPerPage);
	}		
}