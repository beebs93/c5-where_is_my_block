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
	 * @since v0.9.0
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
	 * @since v0.9.0
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
		$this->addHeaderItem($objHh->javascript('wimb.js', 'where_is_my_block'));
		
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
	 * @param mixed $message - Single message string or an array of error message strings
	 * @param string $strSeverity - Severity level ("success", "info", "warn" or "error") - defaults to "info"
	 * @return string
	 * 
	 * @author Brad Beebe
	 * @since v0.9.0
	 * @since v0.9.0.9 	- Changed allowable $message variable types
	 *        			- Changed HTML structure to follow c5 v5.6.0.2 alert message standards
	 */	
	public function getAlert($message, $strSeverity = 'info'){
		$tmplMsg = '<div class="alert alert-%2$s"><button type="button" class="close" data-dismiss="alert">×</button>%1$s</div>';

		// Normalize severity level
		if(!in_array($strSeverity, array('success', 'info', 'warn', 'error'))){
			$strSeverity = 'info';
		}

		$htmMsg = '<div class="ccm-ui" id="ccm-dashboard-result-message">';
		$htmMsg .= '<div class="row"><div class="span12">';

		// If a single message string
		if(is_string($message)){
			$strMsg = nl2br($this->helperObjects['text']->entities($message));

			$htmMsg .= sprintf($tmplMsg, $strMsg, $strSeverity);
		// If an array of message strings
		}elseif(is_array($message)){
			foreach($message as $strMsg){
				$strMsg = nl2br($this->helperObjects['text']->entities($strMsg));

				$htmMsg .= sprintf($tmplMsg, $strMsg, $strSeverity);
			}
		// If some crazy voodoo I have not accounted for, then return an empty string.
		}else{
			return '';
		}

		$htmMsg .= '</div></div>';
		$htmMsg .= '</div>';

		return $htmMsg;
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
		return ((is_numeric($btId)) && $this->arrAllowedBtIds[(int) $btId] === TRUE);
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