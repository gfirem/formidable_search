<?php
/**
 * @package    WordPress
 * @subpackage Formidable, gfirem_adv_search
 * @author     GFireM
 * @copyright  2017
 * @link       http://www.gfirem.com
 * @license    http://www.apache.org/licenses/
 */

wp_nonce_field( 'gfirem_adv_search_metabox_collect_settings', 'gfirem_adv_search_metabox_nonce' );
?>
    <p>
        <input type="checkbox" id="frm_search_enabled" name="frm_search_enabled" value="<?php echo "$enabled_adv_filtering"; ?>" <?php checked( $enabled_adv_filtering, '1' ); ?> /><?php _e( 'Enabled the advance filtering', 'gfirem_adv_search-locale' ); ?><br/>
    </p>
<?php if ( ! empty( $display->frm_where ) ) : ?>
    <div <?php echo "$show_adv_view"; ?> class="frm_search_filters">
        <div class="frm_search_divider">
            <strong><?php _e( 'Select the Filters combinations', 'gfirem_adv_search-locale' ); ?></strong>
            <div class="frm_search_filter_item">
				<?php $i = 0;
				if ( count( $display->frm_where ) > 1 ):
					foreach ( $display->frm_where as $item ) :
						if ( empty( $item ) ) {
							continue;
						}
						$i ++;
						$field  = FrmField::getOne( $item );
						$name   = strlen( $field->name ) > 15 ? substr( $field->name, 0, 15 ) . "..." : $field->name;
						$filter = ( ! empty( $filters ) && ! empty( $filters[ $item ] ) ) ? $filters[ $item ]['filter'] : 'AND';
						echo "$name" . ' [' . esc_html( $field->id ) . '] ';
						if ( $i != count( $display->frm_where ) ): ?>
                            <select <?php echo disabled( count( $display->frm_where ), $i, true ); ?> name="frm_search_field[<?php echo esc_attr( $field->id ); ?>]" id="frm_search_field_filter">
                                <option <?php selected( $filter, 'AND', true ); ?> value="AND"><?php _e( 'AND', 'gfirem_adv_search-locale' ); ?></option>
                                <option <?php selected( $filter, 'OR', true ); ?> value="OR"><?php _e( 'OR', 'gfirem_adv_search-locale' ); ?></option>
                            </select>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else:
					_e( 'You need more than one filter.', 'gfirem_adv_search-locale' );
				endif; ?>
            </div>
        </div>
        <div class="frm_search_divider">
            <strong><?php _e( 'Select Sort combinations', 'gfirem_adv_search-locale' ); ?></strong><br/>
            <i><?php _e( 'You can use [get param=var] shortcode to create a table with a dynamic order form the url', 'gfirem_adv_search-locale' ); ?></i>
            <div class="frm_search_sort_item">
                <?php if ( !empty( $display->frm_order_by ) ): ?>
                <?php
                    foreach ( $display->frm_order_by as $item ) :
		                if ( empty( $item ) ) {
			                continue;
		                }
		                $i ++;
		                $field  = FrmField::getOne( $item );
		                $name   = strlen( $field->name ) > 15 ? substr( $field->name, 0, 15 ) . "..." : $field->name;
		                echo "<p class='gfirem-disabled'>$name" . ' [' . esc_html( $field->id ) . ']  ';
		                echo "<input disabled type='text' name='frm_search_order[". esc_attr( $field->id )."]' id='frm_search_field_order'  value=''/> </p>";
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else:
	_e( '<i>If you don\'t see anything yet, you need to save your View with a Filter(s). Look the <a target="_blank" href="https://formidableforms.com/knowledgebase/filtering-entries/">documentation</a></i>', 'gfirem_adv_search-locale' );
endif; ?>