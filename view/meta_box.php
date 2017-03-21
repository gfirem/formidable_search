<?php
/**
 * @package    WordPress
 * @subpackage Formidable, formidable_search
 * @author     GFireM
 * @copyright  2017
 * @link       http://www.gfirem.com
 * @license    http://www.apache.org/licenses/
 */

wp_nonce_field( 'formidable_search_metabox_collect_settings', 'formidable_search_metabox_nonce' );
?>
<p>
    <input type="checkbox" id="frm_search_enabled" name="frm_search_enabled" value="<?php echo "$enabled_adv_filtering"; ?>" <?php checked( $enabled_adv_filtering, '1' ); ?> /><?php _e( 'Enabled the advance filtering', 'formidable_search' ); ?><br/>
</p>
<?php if ( ! empty( $display->frm_where ) ) : ?>
    <div <?php echo "$show_adv_view"; ?> class="frm_search_filters">
        <strong><?php _e( 'Select the combinations', 'formidable_search' ); ?></strong>
        <div class="frm_search_filter_item">
			<?php foreach ( $display->frm_where as $item ) :
				if ( empty( $item ) ) {
					continue;
				}
				$field = FrmField::getOne( $item );
				$name  = strlen( $field->name ) > 10 ? substr( $field->name, 0, 10 ) . "..." : $field->name;
				?>
                <p>
					<?php echo "$name" . ' [' . esc_html( $field->id ) . '] -->'; ?>
                    <select name="frm_search_field[<?php echo esc_attr( $field->id ); ?>]" id="frm_search_field_filter">
                        <option <?php selected( $filters[ $item ]['filter'], 'AND', true ); ?> value="AND"><?php _e( 'AND', 'formidable_search' ); ?></option>
                        <option <?php selected( $filters[ $item ]['filter'], 'OR', true ); ?> value="OR"><?php _e( 'OR', 'formidable_search' ); ?></option>
                    </select>
                </p>
			<?php endforeach; ?>
        </div>
    </div>
<?php else:
	_e( '<i>if you don\'t see anything yet, you need to save your View with a Filter(s). Look the <a target="_blank" href="https://formidableforms.com/knowledgebase/filtering-entries/">documentation</a></i>', 'formidable_search' );
endif; ?>

