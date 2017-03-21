<?php
/**
 * @package WordPress
 * @subpackage Formidable, formidable_search
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class formidable_search_requirements extends WP_Requirements {
	
	public function __construct( $text_domain = 'WP_Requirements' ) {
		parent::__construct( $text_domain );
	}
	
	/**
	 * Set the plugins requirements
	 *
	 * @return array
	 */
	function getRequirements() {
		$requirements                = array();
		$requirement                 = new WP_PHP_Requirement();
		$requirement->minimumVersion = '5.2.0';
		array_push( $requirements, $requirement );
		$requirement                 = new WP_WordPress_Requirement();
		$requirement->minimumVersion = '4.7.2';
		array_push( $requirements, $requirement );
		$requirement = new WPMU_WordPress_Requirement();
		$requirement->setIsForMultisite( false );
		array_push( $requirements, $requirement );
		$requirement          = new WP_Plugins_Requirement();
		$requirement->plugins = array(
			array( 'id' => 'formidable/formidable.php', 'name' => 'Formidable', 'min_version' => '2.0.0' )
		);
		array_push( $requirements, $requirement );
		
		return $requirements;
	}
}