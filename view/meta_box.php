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
        <strong><?php _e( 'Select the combinations', 'gfirem_adv_search-locale' ); ?></strong>
        <div class="frm_search_filter_item">
			<?php $i = 0;
			foreach ( $display->frm_where as $item ) :
				if ( empty( $item ) ) {
					continue;
				}
				$i ++;
				$field   = FrmField::getOne( $item );
				$name    = strlen( $field->name ) > 10 ? substr( $field->name, 0, 10 ) . "..." : $field->name; ?>
                <p>
					<?php echo "$name" . ' [' . esc_html( $field->id ) . '] '; ?>
					<?php echo ($i == count($display->frm_where))? '': '>> '; ?>
                    <select <?php echo disabled( count( $display->frm_where ), $i, true ); ?> name="frm_search_field[<?php echo esc_attr( $field->id ); ?>]" id="frm_search_field_filter">
                        <option <?php selected( $filters[ $item ]['filter'], 'AND', true ); ?> value="AND"><?php _e( 'AND', 'gfirem_adv_search-locale' ); ?></option>
                        <option <?php selected( $filters[ $item ]['filter'], 'OR', true ); ?> value="OR"><?php _e( 'OR', 'gfirem_adv_search-locale' ); ?></option>
                    </select>
	                <?php echo ($i == count($display->frm_where))? '': '>> '; ?>
                </p>
			<?php endforeach; ?>
        </div>
    </div>
<?php else:
	_e( '<i>if you don\'t see anything yet, you need to save your View with a Filter(s). Look the <a target="_blank" href="https://formidableforms.com/knowledgebase/filtering-entries/">documentation</a></i>', 'gfirem_adv_search-locale' );
endif; ?>

