<?php
/**
 * Displays the languages tab in Polylang settings
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};
?>
<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<?php
			// Displays the language list in a table
			$list_table->display();
			?>
			<div class="metabox-holder">
				<?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				do_meta_boxes( 'toplevel_page_mlang', 'normal', array() );
				?>
			</div>
		</div><!-- col-wrap -->
	</div><!-- col-right -->

	<div id="col-left">
		<div class="col-wrap">

			<div class="form-wrap">
				<h2><?php echo ! empty( $edit_lang ) ? esc_html__( 'Edit language', 'polylang' ) : esc_html__( 'Add new language', 'polylang' ); ?></h2>
				<?php
				// Displays the add ( or edit ) language form
				// Adds noheader=true in the action url to allow using wp_redirect when processing the form
				?>
				<form id="add-lang" method="post" action="admin.php?page=mlang&amp;noheader=true" class="validate">
				<?php
				wp_nonce_field( 'add-lang', '_wpnonce_add-lang' );

				if ( ! empty( $edit_lang ) ) {
					?>
					<input type="hidden" name="pll_action" value="update" />
					<input type="hidden" name="lang_id" value="<?php echo esc_attr( $edit_lang->term_id ); ?>" />
					<?php
				} else {
					?>
					<input type="hidden" name="pll_action" value="add" />
					<?php
				}
				?>
					<div class="form-field">
						<label for="lang_list"><?php esc_html_e( 'Choose a language', 'polylang' ); ?></label>
						<select name="lang_list" id="lang_list">
							<option value=""></option>
							<?php
							foreach ( PLL_Settings::get_predefined_languages() as $language ) {
								printf(
									'<option value="%1$s:%2$s:%3$s:%4$s" data-flag-html="%6$s">%5$s - %2$s</option>' . "\n",
									esc_attr( $language['code'] ),
									esc_attr( $language['locale'] ),
									'rtl' == $language['dir'] ? '1' : '0',
									esc_attr( $language['flag'] ),
									esc_html( $language['name'] ),
									esc_attr( PLL_Language::get_predefined_flag( $language['flag'] ) )
								);
							}
							?>
						</select>
						<p><?php esc_html_e( 'You can choose a language in the list or directly edit it below.', 'polylang' ); ?></p>
					</div>

					<div class="form-field form-required">
						<label for="lang_name"><?php esc_html_e( 'Full name', 'polylang' ); ?></label>
						<?php
						printf(
							'<input name="name" id="lang_name" type="text" value="%s" size="40" aria-required="true" />',
							! empty( $edit_lang ) ? esc_attr( $edit_lang->name ) : ''
						);
						?>
						<p><?php esc_html_e( 'The name is how it is displayed on your site (for example: English).', 'polylang' ); ?></p>
					</div>

					<div class="form-field form-required">
						<label for="lang_locale"><?php esc_html_e( 'Locale', 'polylang' ); ?></label>
						<?php
						printf(
							'<input name="locale" id="lang_locale" type="text" value="%s" size="40" aria-required="true" />',
							! empty( $edit_lang ) ? esc_attr( $edit_lang->locale ) : ''
						);
						?>
						<p><?php esc_html_e( 'WordPress Locale for the language (for example: en_US). You will need to install the .mo file for this language.', 'polylang' ); ?></p>
					</div>

					<div class="form-field">
						<label for="lang_slug"><?php esc_html_e( 'Language code', 'polylang' ); ?></label>
						<?php
						printf(
							'<input name="slug" id="lang_slug" type="text" value="%s" size="40"/>',
							! empty( $edit_lang ) ? esc_attr( $edit_lang->slug ) : ''
						);
						?>
						<p><?php esc_html_e( 'Language code - preferably 2-letters ISO 639-1  (for example: en)', 'polylang' ); ?></p>
					</div>

					<div class="form-field"><fieldset>
						<legend class="pll-legend"><?php esc_html_e( 'Text direction', 'polylang' ); ?></legend>
						<?php
						printf(
							'<label><input name="rtl" type="radio" value="0" %s /> %s</label>',
							checked( ! empty( $edit_lang ) && $edit_lang->is_rtl, false, false ),
							esc_html__( 'left to right', 'polylang' )
						);
						printf(
							'<label><input name="rtl" type="radio" value="1" %s /> %s</label>',
							checked( ! empty( $edit_lang ) && $edit_lang->is_rtl, true, false ),
							esc_html__( 'right to left', 'polylang' )
						);
						?>
						<p><?php esc_html_e( 'Choose the text direction for the language', 'polylang' ); ?></p>
					</fieldset></div>

					<div class="form-field">
						<label for="flag_list"><?php esc_html_e( 'Flag', 'polylang' ); ?></label>
						<select name="flag" id="flag_list">
							<option value=""></option>
							<?php
							$flags = include __DIR__ . '/flags.php';
							foreach ( $flags as $code => $label ) {
								printf(
									'<option value="%s" data-flag-html="%s"%s>%s</option>' . "\n",
									esc_attr( $code ),
									esc_html( PLL_Language::get_flag_html( PLL_Language::get_flag_informations( $code ) ) ),
									selected( isset( $edit_lang->flag_code ) && $edit_lang->flag_code === $code, true, false ),
									esc_html( $label )
								);
							}
							?>
						</select>
						<p><?php esc_html_e( 'Choose a flag for the language.', 'polylang' ); ?></p>
					</div>

					<div class="form-field">
						<label for="lang_order"><?php esc_html_e( 'Order', 'polylang' ); ?></label>
						<?php
						printf(
							'<input name="term_group" id="lang_order" type="text" value="%d" />',
							! empty( $edit_lang ) ? esc_attr( $edit_lang->term_group ) : ''
						);
						?>
						<p><?php esc_html_e( 'Position of the language in the language switcher', 'polylang' ); ?></p>
					</div>
					<?php
					if ( ! empty( $edit_lang ) ) {
						/**
						 * Fires after the Edit Language form fields are displayed.
						 *
						 * @since 1.7.10
						 *
						 * @param PLL_Language $lang language being edited.
						 */
						do_action( 'pll_language_edit_form_fields', $edit_lang );
					} else {
						/**
						 * Fires after the Add Language form fields are displayed.
						 *
						 * @since 1.7.10
						 */
						do_action( 'pll_language_add_form_fields' );
					}

					submit_button( ! empty( $edit_lang ) ? __( 'Update', 'polylang' ) : __( 'Add new language', 'polylang' ) ); // since WP 3.1
					?>
				</form>
			</div><!-- form-wrap -->
		</div><!-- col-wrap -->
	</div><!-- col-left -->
</div><!-- col-container -->
