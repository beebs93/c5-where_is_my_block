<?php 
defined('C5_EXECUTE') or die(_('Access Denied.'));

class PageBlockSearchHelper{
	/**
	 * Fetches all of the blocks IDs of a specific block type ID on a specific page
	 *
	 * @see Concrete5_Model_Collection->getBlocks
	 * @param Page $objPage - Any page
	 * @param int $intBtId - A block type ID
	 * @return array
	 *
	 * @author Brad Beebe
	 * @since v1.0.0.2
	 */
	public function getPageBlockIdsByBlockTypeId(Page $objPage, $intBtId){
		$arrBlockIds = array();

		$db = Loader::db();
		
		$arrValues = array($objPage->getCollectionID(), $objPage->getVersionID(), (int) $intBtId);
		
	 	// While there exists a native method to retrieve all block objects of a specific page,
	 	// it has a large performance cost so we need to bypass it. We only want an array of
	 	// block IDs so we can afford giving up the block objects.
		$sqlBlocks = '
		SELECT
			Blocks.bID
		FROM
			CollectionVersionBlocks
		INNER JOIN
			Blocks ON (CollectionVersionBlocks.bID = Blocks.bID)
		INNER JOIN
			BlockTypes ON (Blocks.btID = BlockTypes.btID)
		WHERE
			CollectionVersionBlocks.cID = ?
				AND	
			(CollectionVersionBlocks.cvID = ?
				OR
			CollectionVersionBlocks.cbIncludeAll = 1)
				AND
			Blocks.btID = ?
		ORDER BY
			CollectionVersionBlocks.cID ASC';
		
		$arrResults = $db->GetAll($sqlBlocks, $arrValues);

		if(is_array($arrResults)){
			foreach($arrResults as $arrBlock){
				$arrBlockIds[] = (int) $arrBlock['bID'];
			}
		}

		return $arrBlockIds;
	}
}
?>