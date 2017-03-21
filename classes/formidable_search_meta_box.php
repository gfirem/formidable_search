<?php
/**
 * @package WordPress
 * @subpackage Formidable, formidable_search
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class formidable_search_meta_box {
	
	private $version = '1.0.0';
	
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_frm_display', array( $this, 'save_meta_boxes_data' ) );
		add_action( 'admin_footer', array( $this, 'add_script' ) );
		add_filter( 'frm_where_filter', array( $this, 'search_filter_query' ), 10, 2 );
	}
	
	/**
	 * Add meta box
	 *
	 * @param WP_Post $post The post object
	 *
	 */
	public function add_meta_boxes( $post ) {
		add_meta_box(
			'formidable_search_meta_box',
			__( 'Advance Search Filter', 'formidable_search' ),
			array( $this, 'formidable_search_meta_box_callback' ),
			'frm_display', 'side'
		);
	}
	
	/**
	 * Build the meta box view
	 *
	 * @param $post WP_Post
	 */
	public function formidable_search_meta_box_callback( $post ) {
		$enabled_adv_filtering = get_post_meta( $post->ID, '_enabled_adv_filtering', true );
		$show_adv_view         = '';
		if ( empty( $enabled_adv_filtering ) ) {
			$show_adv_view         = 'style="display:none;"';
			$enabled_adv_filtering = '0';
		} else {
			$enabled_adv_filtering = '1';
		}
		$display      = FrmProDisplay::getOne( $post->ID, false, true );
		$data_encoded = get_post_meta( $post->ID, '_formidable_search_collect_setting', true );
		$filters      = array();
		if ( ! empty( $data_encoded ) ) {
			$filters = $data_encoded;
		}
		include FSE_VIEW_PATH . 'meta_box.php';
	}
	
	/**
	 * Store custom field meta box data
	 *
	 * @param int $post_id The post ID.
	 */
	function save_meta_boxes_data( $post_id ) {
		if ( ! wp_verify_nonce( $_POST['formidable_search_metabox_nonce'], 'formidable_search_metabox_collect_settings' ) ) {
			return;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		if ( ! isset( $_POST['frm_search_enabled'] ) ) {
			delete_post_meta( $post_id, '_enabled_adv_filtering' );
			delete_post_meta( $post_id, '_formidable_search_collect_setting' );
			
			return;
		}
		
		update_post_meta( $post_id, '_enabled_adv_filtering', sanitize_text_field( $_POST['frm_search_enabled'] ) );
		
		if ( ! empty( $_POST['frm_search_field'] ) && is_array( $_POST['frm_search_field'] ) ) {
			$filters = array();
			foreach ( $_POST['frm_search_field'] as $field => $filter ) {
				if ( empty( $field ) || empty( $filter ) ) {
					continue;
				}
				$filters[ sanitize_text_field( strval( $field ) ) ] = array( 'filter' => sanitize_text_field( $filter ), 'where' => $this->get_where_val( $field ) );
			}
			if ( ! empty( $filters ) ) {
				update_post_meta( $post_id, '_formidable_search_collect_setting', $filters );
			}
		}
	}
	
	/**
	 * Add script needed
	 */
	public function add_script() {
		global $current_screen;
		if ( $current_screen->id == 'frm_display' ) {
			$base_url = plugin_dir_url( __FILE__ ) . 'assets/';
			wp_enqueue_script( 'formidable_search', $base_url . 'js/formidable_search.js', array( "jquery" ), $this->version, true );
		}
	}
	
	public function search_filter_query( $where, $args ) {
		$enabled_adv_filtering = get_post_meta( $args['display']->ID, '_enabled_adv_filtering', true );
		if ( ! empty( $enabled_adv_filtering ) ) {
			$data_encoded = get_post_meta( $args['display']->ID, '_formidable_search_collect_setting', true );
			if ( ! empty( $data_encoded ) && is_array( $data_encoded ) ) {
				if ( array_key_exists( $args['where_opt'], $data_encoded ) ) {
					$where_array = array();
					$single_line = array( 'text', 'email', 'textarea', 'url', 'number' );
					
					foreach ( $data_encoded as $field_key => $field_term ) {
						if ( ! empty( $_GET[ $field_term['where'] ] ) ) {
							$field_search_value = esc_attr( $_GET[ $field_term['where'] ] );
							$type               = FrmField::get_type( $field_key );
							if ( in_array( $type, $single_line ) ) {
								$where_array[ $field_key ] = " (meta_value like '%" . $field_search_value . "%' and fi.id = " . $field_key . ") ";
							} else {
								$options   = $this->get_array_of_options( $field_search_value );
								$b         = 1;
								$where_str = '';
								foreach ( $options as $option ) {
									if ( $b > 1 ) {
										$where_str .= ' OR ';
									}
									$where_str .= " (meta_value like '%" . trim( $option ) . "%' and fi.id = " . $field_key . ") ";
									$b ++;
								}
								$where_array[ $field_key ] = $where_str;
							}
						}
					}
					if ( ! empty( $where_array ) ) {
						$i         = 1;
						$where_str = '';
						foreach ( $where_array as $field_key => $field_term ) {
							$where_str .= ' ' . $field_term . ' ';
							if ( $i != count( $where_array ) ) {
								$where_str .= $data_encoded[ $field_key ]['filter'];
							}
							$i ++;
						}
						
						return $where_str;
					}
				}
			}
		}
		
		return $where;
	}
	
	private function get_array_of_options( $str ) {
		return explode( ',', $str );
	}
	
	private function clean_the_where_val( $where_val ) {
		$shortCodes = FrmFieldsHelper::get_shortcodes( $where_val, $_POST['form_id'] );
		foreach ( $shortCodes[3] as $tag ) {
			preg_match_all( '/param=(.*?)$/', $tag, $match );
			if ( ! empty( $match[1][0] ) ) {
				return $match[1][0];
			}
		}
		
		return $where_val;
	}
	
	private function get_where_val( $field ) {
		if ( ! empty( $field ) ) {
			if ( ! empty( $_POST['options'] ) && ! empty( $_POST['options']['where'] ) && ! empty( $_POST['options']['where_val'] ) ) {
				foreach ( $_POST['options']['where'] as $where_key => $where_field ) {
					if ( $where_field == strval( $field ) ) {
						return $this->clean_the_where_val( $_POST['options']['where_val'][ $where_key ] );
					}
				}
			}
		}
		
		return '';
	}
}