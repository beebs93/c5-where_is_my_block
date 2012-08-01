<?php      
defined('C5_EXECUTE') or die('Access Denied.');

class WhereIsMyBlockPackage extends Package{
	protected $pkgHandle = 'where_is_my_block';
	protected $appVersionRequired = '5.4.2.2';
	protected $pkgVersion = '1.0';
	
	
	/**
	 * Returns the package description
	 * 
	 * @return string
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */
	public function getPackageDescription(){
		return t('Lists the pages that contain a specific block type');
	}
	
	
	/**
	 * Returns the package name
	 *
	 * @return string
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */	
	public function getPackageName(){
		// The willpower it took to not name this, "Dude, Where's my Block?" was biblical
		return t('Where Is My Block?');
	}
	
	
	/**
	 * Installs package and dashboard page
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since July 12, 2012
	 */		
	public function install(){
		Loader::model('single_page');
		
		$objPkg = parent::install();
		
		SinglePage::add('/dashboard/blocks/where-is-my-block', $objPkg);		
	}
}