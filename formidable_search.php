<?php
/**
 * @package           formidable_search
 *
 * @wordpress-plugin
 * Plugin Name:       Formidable Advance Search Filters
 * Description:       Formidable advance search filters, set it from the editor view
 * Version:           1.0.0
 * Author:            gfirem
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 * Network:           True
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'formidable_search' ) ) {
	
	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'formidable_search_fs.php';
	new formidable_search_fs();
	
	class formidable_search {
		
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;
		protected $requirements;
		
		/**
		 * Initialize the plugin.
		 */
		private function __construct() {
			define( 'FSE_BASE_FILE', trailingslashit( str_replace( "\\", "/", plugin_dir_path( __FILE__ ) ) ) . 'formidable_search.php' );
			define( 'FSE_VIEW_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR );
			define( 'FSE_CLASSES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR );
			define( 'FSE_INCLUDES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR );
			
			require_once FSE_INCLUDES_PATH . 'Requirements.php';
			require_once FSE_CLASSES_PATH . 'formidable_search_requirements.php';
			$this->requirements = new formidable_search_requirements();
			if ( $this->requirements->satisfied() ) {
				require_once FSE_CLASSES_PATH . 'formidable_search_meta_box.php';
				new formidable_search_meta_box();
			} else {
				$fauxPlugin = new WP_Faux_Plugin( 'Formidable Search', $this->requirements->getResults() );
				$fauxPlugin->show_result( FSE_BASE_FILE );
			}
			
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
		
		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'gfirem-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
		
	}
	
	add_action( 'plugins_loaded', array( 'formidable_search', 'get_instance' ) );
}
