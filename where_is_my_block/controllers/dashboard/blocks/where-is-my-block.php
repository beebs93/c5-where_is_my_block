<?php
defined('C5_EXECUTE') or die('Access Denied.');

class DashboardBlocksWhereIsMyBlockController extends DashboardBaseController{
	protected $arrBlockTypes = array();
	protected $arrAllowedBtIds = array();
	protected $arrItemsPerPage = array(10, 25, 50, 100, 500);
	protected $arrSortableCols = array('page_name', 'page_path', 'instances');
	public $helpers = array('concrete/dashboard', 'navigation', 'text');
	
		
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
		// information from each block type object that we can search for
		$arrAllBlockTypes = BlockTypeList::getInstalledList();
		
		foreach($arrAllBlockTypes as $objBt){
			// Skip any internal block types (e.g. Dashboard blocks)
			if($objBt->isBlockTypeInternal()) continue;
			
			$arrBtInfo = array(
				'id' => (int) $objBt->btID,
				'handle' => (string) $objBt->btHandle,
				'packageId' => (int) $objBt->pkgID,
				'name' => (string) $objBt->btName,
				'description' => (string) $objBt->btDescription,
				'category' => $objBt->isCoreBlockType() ? 'core' : 'third_party'
			);
			
			$this->arrBlockTypes[$arrBtInfo['id']] = $arrBtInfo;
			
			// Add block type ID to list of allowed IDs
			$this->arrAllowedBtIds[$arrBtInfo['id']] = TRUE;
		}
		
		// Sort the array by category then by handle
		// We skip checking for a filled array since concrete5
		// should always have at least the core blocks installed
		// Should someone manually remove ALL the block types in their
		// c5 install then obviously that will break this addon, but in
		// that situation I have a sinking feeling this addon will be
		// the least of their problems
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
		$this->set('objDh', $this->helperObjects['concrete_dashboard']);
		$this->set('objNh', $this->helperObjects['navigation']);
		$this->set('objTh', $this->helperObjects['text']);
		$this->set('objPkg', Loader::package('where_is_my_block'));
		
		parent::on_start();
	}
	
	
	/**
	 * Single page view
	 * Retrieves the list of core and 3rd party blocks types
	 * 
	 * @return void
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function view(){
		$objUh = Loader::helper('concrete/urls');
		$objHh = Loader::helper('html');
		
		$this->addHeaderItem($objHh->css('wimb.css', 'where_is_my_block'));
		$this->addHeaderItem('<script type="text/javascript">var WIMB_TOOLS_URL = "' . $objUh->getToolsURL('page_block_list.php', 'where_is_my_block') . '";</script>');
		$this->addHeaderItem($objHh->javascript('wimb.min.js', 'where_is_my_block'));
		
		// Set form options in view scope
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
		$tmpAlert = '
		<div class="ccm-ui" id="ccm-dashboard-result-message">
			<div class="row">
				<div class="span16">
					<div class="alert-message %2$s">%1$s</div>
				</div>
			</div>
		</div>';
		
		return preg_replace('/[\r\n\t]/', '', sprintf($tmpAlert, (string) $strMsg, (string) $strSeverity));
	}
	
	
	/**
	 * Checks if a block type ID is in the list of allowed block type IDs that can be searched
	 * 
	 * @param mixed $btId - A block type ID
	 * @return boolean
	 * 
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function isAllowedBlockTypeId($btId){
		return (is_numeric($btId)) && array_key_exists($btId, $this->arrAllowedBtIds) && $this->arrAllowedBtIds[$btId] === TRUE;
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