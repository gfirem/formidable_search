<?php
/**
 * @package    WordPress
 * @subpackage Formidable, gfirem_adv_search
 * @author     GFireM
 * @copyright  2017
 * @link       http://www.gfirem.com
 * @license    http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class gfirem_adv_search_meta_box {
	
	private $version = '1.0.0';
	private $add_scroll_script = false;
	private $display_id;
	private $entry_ids;
	
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_frm_display', array( $this, 'save_meta_boxes_data' ) );
		add_action( 'admin_footer', array( $this, 'add_script' ) );
		add_action( 'wp_footer', array( $this, 'add_script' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );
		add_filter( 'frm_where_filter', array( $this, 'search_filter_query' ), 10, 2 );
		add_filter( 'frm_display_filter_opt', array( $this, 'new_fields_vars' ), 10, 1 );
		if ( gfirem_adv_search_fs::getFreemius()->is_plan__premium_only( 'professional' ) ) {
			add_filter( 'frm_filter_view', array( $this, 'filter_view__premium_only' ), 10, 1 );
			add_filter( 'frm_display_entries_content', array( $this, 'display_entries_content__premium_only' ), 10, 4 );//Pro
			add_filter( 'frm_no_entries_message', array( $this, 'no_entries_message__premium_only' ), 10, 2 );//Pro
		}
	}
	
	public function no_entries_message__premium_only( $message, $args ) {
		$this->add_scroll_script = true;
		$this->display_id        = $args['display']->ID;
		
		return $message;
	}
	
	public function new_fields_vars( $args ) {
		$args['use_ids']     = false;
		$args['after_where'] = false;
		
		return $args;
	}
	
	public function filter_view__premium_only( $view ) {
		$data_encoded = get_post_meta( $view->ID, '_gfirem_adv_search_order_setting', true );
		if ( ! empty( $data_encoded ) && is_array( $data_encoded ) && is_array( $view->frm_order_by ) ) {
			foreach ( $view->frm_order_by as $order_key => $order_term ) {
				if ( array_key_exists( $order_term, $data_encoded ) ) {
					if ( isset( $_GET[ $data_encoded[ $order_term ] ] ) ) {
						$custom_order = FrmAppHelper::get_param( $data_encoded[ $order_term ] );
						if ( ! empty( $custom_order ) && ( $custom_order == 'ASC' || $custom_order == 'DESC' ) ) {
							$view->frm_order[ $order_key ] = $custom_order;
						}
					}
				}
			}
		}
		
		return $view;
	}
	
	public function display_entries_content__premium_only( $new_content, $entries, $shortcodes, $display ) {
		if ( ! empty( $entries ) && count( $entries ) > 0 ) {
			$this->add_scroll_script = true;
			$this->display_id        = $display->ID;
		}
		
		return $new_content;
	}
	
	/**
	 * Add script needed
	 */
	public function add_script() {
		global $current_screen;
		if ( ( ! empty( $current_screen ) && $current_screen->id == 'frm_display' ) || $this->add_scroll_script ) {
			wp_enqueue_script( 'gfirem_adv_search', FSE_JS_PATH . 'gfirem_adv_search.js', array( "jquery" ), $this->version, true );
			$params = array();
			if ( gfirem_adv_search_fs::getFreemius()->is_plan__premium_only( 'professional' ) ) {
				$go                 = true;
				$scroll_to_if_query = get_post_meta( $this->display_id, '_frm_enabled_scroll_if_query', true );
				if ( ! empty( $scroll_to_if_query ) ) {
					if ( strpos( $scroll_to_if_query, ',' ) === false ) {
						$go = ( isset( $_GET[ $scroll_to_if_query ] ) );
					} else {
						$params = explode( ',', $scroll_to_if_query );
						if ( is_array( $params ) ) {
							$go = false;
							foreach ( $params as $param ) {
								if ( isset( $_GET[ trim( $param ) ] ) ) {
									$go = true;
									break;
								}
							}
						}
					}
				}
				
				if ( $this->add_scroll_script && $go ) {
					wp_enqueue_script( 'animatescroll', FSE_JS_PATH . 'animatescroll.min.js', array( "jquery" ), $this->version, true );
					if ( ! empty( $this->display_id ) ) {
						$params['scroll_to']         = get_post_meta( $this->display_id, '_frm_enabled_scroll_to', true );
						$params['scroll_to_padding'] = get_post_meta( $this->display_id, '_frm_enabled_scroll_padding', true );
					}
				}
			}
			wp_localize_script( 'gfirem_adv_search', 'gfirem_adv_search', $params );
		}
	}
	
	/**
	 * Include styles in admin
	 *
	 * @param $hook
	 */
	public function enqueue_style( $hook ) {
		global $current_screen;
		if ( $current_screen->id == 'frm_display' ) {
			wp_enqueue_style( 'gfirem_adv_search', FSE_CSS_PATH . 'gfirem_adv_search.css', array(), $this->version );
		}
	}
	
	/**
	 * Add meta box
	 *
	 * @param WP_Post $post The post object
	 *
	 */
	public function add_meta_boxes( $post ) {
		add_meta_box(
			'gfirem_adv_search_meta_box',
			__( 'Advance Search Filter & Sort', 'gfirem_adv_search-locale' ),
			array( $this, 'gfirem_adv_search_meta_box_callback' ),
			'frm_display'
		);
	}
	
	/**
	 * Build the meta box view
	 *
	 * @param $post WP_Post
	 */
	public function gfirem_adv_search_meta_box_callback( $post ) {
		$enabled_adv_filtering = get_post_meta( $post->ID, '_enabled_adv_filtering', true );
		$show_adv_view         = '';
		if ( empty( $enabled_adv_filtering ) ) {
			$show_adv_view         = 'style="display:none;"';
			$enabled_adv_filtering = '0';
		} else {
			$enabled_adv_filtering = '1';
		}
		$display      = FrmProDisplay::getOne( $post->ID, false, true );
		$data_encoded = get_post_meta( $post->ID, '_gfirem_adv_search_collect_setting', true );
		$filters      = array();
		if ( ! empty( $data_encoded ) ) {
			$filters = $data_encoded;
		}
		
		$orders                      = array();
		$frm_enabled_scroll_to       = '';
		$frm_enabled_scroll_padding  = '';
		$frm_enabled_scroll_if_query = '';
		if ( gfirem_adv_search_fs::getFreemius()->is_plan__premium_only( 'professional' ) ) {
			$data_encoded = get_post_meta( $post->ID, '_gfirem_adv_search_order_setting', true );
			if ( ! empty( $data_encoded ) ) {
				$orders = $data_encoded;
			}
			$frm_enabled_scroll_to       = get_post_meta( $post->ID, '_frm_enabled_scroll_to', true );
			$frm_enabled_scroll_padding  = get_post_meta( $post->ID, '_frm_enabled_scroll_padding', true );
			$frm_enabled_scroll_if_query = get_post_meta( $post->ID, '_frm_enabled_scroll_if_query', true );
		}
		include FSE_VIEW_PATH . 'meta_box.php';
	}
	
	/**
	 * Store custom field meta box data
	 *
	 * @param int $post_id The post ID.
	 */
	function save_meta_boxes_data( $post_id ) {
		if ( ! empty( $_POST['gfirem_adv_search_metabox_nonce'] ) && ! wp_verify_nonce( $_POST['gfirem_adv_search_metabox_nonce'], 'gfirem_adv_search_metabox_collect_settings' ) ) {
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
			delete_post_meta( $post_id, '_gfirem_adv_search_collect_setting' );
		} else {
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
					update_post_meta( $post_id, '_gfirem_adv_search_collect_setting', $filters );
				}
			}
		}
		
		if ( gfirem_adv_search_fs::getFreemius()->is_plan__premium_only( 'professional' ) ) {
			if ( ! isset( $_POST['frm_enabled_scroll_to'] ) ) {
				delete_post_meta( $post_id, '_frm_enabled_scroll_to' );
				delete_post_meta( $post_id, '_frm_enabled_scroll_padding' );
				delete_post_meta( $post_id, '_frm_enabled_scroll_if_query' );
			} else if ( ! empty( $_POST['frm_enabled_scroll_to'] ) &&
			            ! empty( $_POST['frm_enabled_scroll_padding'] ) && ! empty( $_POST['frm_enabled_scroll_if_query'] )
			) {
				update_post_meta( $post_id, '_frm_enabled_scroll_to', sanitize_text_field( $_POST['frm_enabled_scroll_to'] ) );
				update_post_meta( $post_id, '_frm_enabled_scroll_padding', sanitize_text_field( $_POST['frm_enabled_scroll_padding'] ) );
				update_post_meta( $post_id, '_frm_enabled_scroll_if_query', sanitize_text_field( $_POST['frm_enabled_scroll_if_query'] ) );
			}
			
			if ( ! empty( $_POST['frm_search_order'] ) && is_array( $_POST['frm_search_order'] ) ) {
				$order = array();
				foreach ( $_POST['frm_search_order'] as $field => $filter ) {
					if ( empty( $field ) || empty( $filter ) ) {
						continue;
					}
					$order[ sanitize_text_field( strval( $field ) ) ] = sanitize_text_field( $filter );
				}
				if ( ! empty( $order ) ) {
					update_post_meta( $post_id, '_gfirem_adv_search_order_setting', $order );
				}
			}
		}
	}
	
	public function search_filter_query( $where, $args ) {
		$enabled_adv_filtering = get_post_meta( $args['display']->ID, '_enabled_adv_filtering', true );
		if ( ! empty( $enabled_adv_filtering ) ) {
			$data_encoded = get_post_meta( $args['display']->ID, '_gfirem_adv_search_collect_setting', true );
			if ( ! empty( $data_encoded ) && is_array( $data_encoded ) ) {
				if ( array_key_exists( $args['where_opt'], $data_encoded ) ) {
					$where_str_union = array();
					$where_str       = array();
					foreach ( $data_encoded as $field_key => $field_term ) {
						if ( ! empty( $_GET[ $field_term['where'] ] ) ) {
							$field_search_value              = esc_attr( $_GET[ $field_term['where'] ] );
							$filter_args                     = array( 'is_draft' => $args['drafts'] );
							$field_form_id                   = FrmField::get_type( $where['fi.id'], 'form_id' );
							$filter_args['return_parent_id'] = ( $field_form_id != $args['form_id'] );
							if ( $filter_args['return_parent_id'] ) {
								$where_statement['parent_item_id'] = $args['entry_ids'];
							} else {
								$where_statement['item_id'] = $args['entry_ids'];
							}
							
							$new_where['fi.id'] = $field_key;
							$new_where_key      = 'meta_value ' . ( in_array( FrmField::get_type( $field_key ), array( 'number', 'scale' ) ) ? ' +0 ' : '' ) . FrmDb::append_where_is( $this->get_union_arg( $args['display'], $field_key ) );
							$new_where[0]       = array( $new_where_key => $this->get_array_of_options( $field_search_value ) );
							$query              = array();
							self::get_ids_query( $new_where, '', '', true, $filter_args, $query );
							
							if ( $field_form_id != $args['form_id'] ) {
								$where_str[ $field_key ] = ' parent_item_id IN( ' . implode( ' ', $query ) . ' ) ';
							} else {
								$where_str[ $field_key ] = ' it.item_id IN( ' . implode( ' ', $query ) . ' ) ';
							}
							$where_str_union[ $field_key ] = $field_term['filter'];
							unset( $new_where );
						}
					}
					if ( ! empty( $where_str_union ) ) {
						$open_or         = false;
						$where_str_final = '';
						for ( $i = $open_position = 0; $i < count( $where_str_union ); $i ++ ) {
							$current = current( $where_str_union );
							$key     = key( $where_str_union );
							$next    = next( $where_str_union );
							if ( ( $current == 'OR' ) && ! $open_or && count( $where_str_union ) > 1 ) {
								$where_str_final .= ' ( ';
								$open_or         = true;
								$open_position   = $i;
							}
							$where_str_final .= ' ' . $where_str[ $key ];
							if ( $i != count( $where_str ) - 1 ) {
								$where_str_final .= ' ' . $data_encoded[ $key ]['filter'];
							}
							if ( count( $where_str_union ) > 1 && $open_or && $i != $open_position ) {
								$close_tag = false;
								if ( ! empty( $next ) && $next == 'AND' ) {
									$close_tag = true;
								}
								if ( $i == count( $where_str ) - 1 ) {
									$close_tag = true;
								}
								if ( $close_tag ) {
									$where_str_final .= ' ) ';
									$open_or         = false;
								}
							}
						}
						
						return $where_str_final;
					}
				}
			}
		}
		
		return $where;
	}
	
	private static function get_ids_query( $where, $order_by, $limit, $unique, $args, array &$query ) {
		global $wpdb;
		$query[] = 'SELECT';
		
		$defaults = array( 'return_parent_id' => false );
		$args     = array_merge( $defaults, $args );
		
		if ( $args['return_parent_id'] ) {
			$query[] = $unique ? 'DISTINCT(e.parent_item_id)' : 'e.parent_item_id';
		} else {
			$query[] = $unique ? 'DISTINCT(it.item_id)' : 'it.item_id';
		}
		
		$query[] = 'FROM ' . $wpdb->prefix . 'frm_item_metas it LEFT OUTER JOIN ' . $wpdb->prefix . 'frm_fields fi ON it.field_id=fi.id';
		
		$query[] = 'INNER JOIN ' . $wpdb->prefix . 'frm_items e ON (e.id=it.item_id)';
		if ( is_array( $where ) ) {
			if ( ! $args['is_draft'] ) {
				$where['e.is_draft'] = 0;
			} else if ( $args['is_draft'] == 1 ) {
				$where['e.is_draft'] = 1;
			}
			
			if ( ! empty( $args['user_id'] ) ) {
				$where['e.user_id'] = $args['user_id'];
			}
			$query[] = FrmAppHelper::prepend_and_or_where( ' WHERE ', $where ) . $order_by . $limit;
			
			return;
		}
		
		$draft_where = '';
		$user_where  = '';
		if ( ! $args['is_draft'] ) {
			$draft_where = $wpdb->prepare( ' AND e.is_draft=%d', 0 );
		} else if ( $args['is_draft'] == 1 ) {
			$draft_where = $wpdb->prepare( ' AND e.is_draft=%d', 1 );
		}
		
		if ( ! empty( $args['user_id'] ) ) {
			$user_where = $wpdb->prepare( ' AND e.user_id=%d', $args['user_id'] );
		}
		
		if ( strpos( $where, ' GROUP BY ' ) ) {
			// don't inject WHERE filtering after GROUP BY
			$parts = explode( ' GROUP BY ', $where );
			$where = $parts[0];
			$where .= $draft_where . $user_where;
			$where .= ' GROUP BY ' . $parts[1];
		} else {
			$where .= $draft_where . $user_where;
		}
		
		// The query has already been prepared
		$query[] = FrmAppHelper::prepend_and_or_where( ' WHERE ', $where ) . $order_by . $limit;
	}
	
	private function get_array_of_options( $str ) {
		if ( strpos( $str, ',' ) !== false ) {
			return explode( ',', $str );
		} else {
			return $str;
		}
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
	
	private function get_union_arg( $display_obj, $field_id ) {
		if ( ! empty( $display_obj ) && ! empty( $display_obj->frm_where ) && is_array( $display_obj->frm_where ) ) {
			foreach ( $display_obj->frm_where as $key => $id ) {
				if ( intval( $field_id ) == $id ) {
					if ( ! empty( $display_obj->frm_where_is ) && is_array( $display_obj->frm_where_is ) ) {
						return $display_obj->frm_where_is[ $key ];
					}
				}
			}
		}
		
		return 'like';
	}
	
	public static function get_extra_option( $option ) {
		$result = array(
			'id'         => __( 'Entry ID', 'formidable' ),
			'created_at' => __( 'Entry creation date', 'formidable' ),
			'updated_at' => __( 'Entry update date', 'formidable' ),
			'rand'       => __( 'Random', 'formidable' ),
		);
		
		if ( empty( $option ) ) {
			return $result;
		} else {
			return $result[ $option ];
		}
	}
}