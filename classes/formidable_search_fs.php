<?php
/**
 * @package    WordPress
 * @subpackage Formidable, formidable_search
 * @author     GFireM
 * @copyright  2017
 * @link       http://www.gfirem.com
 * @license    http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class formidable_search_fs {
	
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;
	
	public function __construct() {
		$this->formidable_search_fs();
	}
	
	/**
	 * @return Freemius
	 */
	public static function getFreemius() {
		global $formidable_search_fs;
		
		return $formidable_search_fs;
	}
	
	// Create a helper function for easy SDK access.
	public function formidable_search_fs() {
		global $formidable_search_fs;
		
		if ( ! isset( $formidable_search_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/../includes/freemius/start.php';
			
			$formidable_search_fs = fs_dynamic_init( array(
				'id'             => '906',
				'slug'           => 'formidable_search',
				'type'           => 'plugin',
				'public_key'     => 'pk_a73d66ca3939d2b76c2bca1d8aa22',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'menu'           => array(
					'first-path' => 'plugins.php',
					'account'    => false,
					'contact'    => false,
					'support'    => false,
				),
			) );
		}
		
		return $formidable_search_fs;
	}
	
	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
}