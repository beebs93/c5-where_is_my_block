<?php       
defined('C5_EXECUTE') or die(_('Access Denied.'));

class WhereIsMyBlockPackage extends Package{
	protected $pkgHandle = 'where_is_my_block';
	protected $appVersionRequired = '5.6.0.2';
	protected $pkgVersion = '1.1';
	
	
	/**
	 * Returns the package description
	 * 
	 * @return string
	 *
	 * @author Brad Beebe
	 * @since v0.9.0
	 */
	public function getPackageDescription(){
		return t('Lists the pages that contain a specific block type.');
	}
	
	
	/**
	 * Returns the package name
	 *
	 * @return string
	 *
	 * @author Brad Beebe
	 * @since v0.9.0
	 */	
	public function getPackageName(){
		// It took a LOT of willpower not to name this "Duuuude, where's my block?"
		return t('Where Is My Block?');
	}
	
	
	/**
	 * Installs package and dashboard page
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v0.9.0
	 */		
	public function install(){
		Loader::model('single_page');
		
		$objPkg = parent::install();
		
		$objPage = SinglePage::add('/dashboard/blocks/where-is-my-block', $objPkg);
		$objPage->setAttribute('icon_dashboard', 'icon-search');
	}


	/**
	 * Upgrades package
	 *
	 * @return void
	 *
	 * @author Brad Beebe
	 * @since v1.0.0.1
	 */	
	public function upgrade(){
		parent::upgrade();

		// Delete any saved form options (this will also force next search
		// to refresh any previously cached data)
		setcookie('wimb_form_options', '', time() - 1000, '/');
	}
}