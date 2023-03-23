<?php
/**
 * Displays the wizard languages step
 *
 * @package Polylang
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$existing_languages = $this->model->get_languages_list();
$default_language   = count( $existing_languages ) > 0 ? $this->options['default_lang'] : null;
$languages_list = array_diff_key(
	PLL_Settings::get_predefined_languages(),
	wp_list_pluck( $existing_languages, 'locale', 'locale' )
);
?>
<div id="language-fields"></div>
<p class="languages-setup">
	<?php esc_html_e( 'This wizard will help you configure your Polylang settings, and get you started quickly with your multilingual website.', 'polylang' ); ?>
</p>
<p class="languages-setup">
	<?php esc_html_e( 'First we are going to define the languages that you will use on your website.', 'polylang' ); ?>
</p>
<h2><?php esc_html_e( 'Languages', 'polylang' ); ?></h2>
<div id="messages">
</div>
<div class="form-field">
	<label for="lang_list"><?php esc_html_e( 'Select a language to be added', 'polylang' ); ?></label>
	<div class="select-language-field">
		<select name="lang_list" id="lang_list">
			<option value=""></option>
			<?php
			foreach ( $languages_list as $language ) {
				printf(
					'<option value="%1$s" data-flag-html="%3$s" data-language-name="%2$s" >%2$s - %1$s</option>' . "\n",
					esc_attr( $language['locale'] ),
					esc_attr( $language['name'] ),
					esc_attr( PLL_Language::get_predefined_flag( $language['flag'] ) )
				);
			}
			?>
		</select>
		<div class="action-buttons">
			<button type="button"
				class="button-primary button"
				value="<?php esc_attr_e( 'Add new language', 'polylang' ); ?>"
				id="add-language"
				name="add-language"
			>
				<span class="dashicons dashicons-plus"></span><?php esc_html_e( 'Add new language', 'polylang' ); ?>
			</button>
		</div>
	</div>
</div>
<table id="languages" class="striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Language', 'polylang' ); ?></th>
			<th><?php esc_html_e( 'Remove', 'polylang' ); ?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
<table id="defined-languages" class="striped<?php echo empty( $existing_languages ) ? ' hide' : ''; ?>">
	<?php if ( ! is_null( $default_language ) ) : ?>
		<caption><span class="icon-default-lang"></span> <?php esc_html_e( 'Default language', 'polylang' ); ?></caption>
	<?php endif; ?>
	<thead>
		<tr>
			<th><?php esc_html_e( 'Languages already defined', 'polylang' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $existing_languages as $lg ) {
		printf(
			'<tr><td>%3$s<span>%2$s - %1$s</span> %4$s</td></tr>' . "\n",
			esc_attr( $lg->locale ),
			esc_html( $lg->name ),
			$lg->flag,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$lg->is_default ? ' <span class="icon-default-lang"><span class="screen-reader-text">' . esc_html__( 'Default language', 'polylang' ) . '</span></span>' : ''
		);
	}
	?>
	</tbody>
</table>

<div id="dialog">
	<p>
	<?php
	printf(
		/* translators: %1$s: is a language flag image, %2$s: is a language native name */
		esc_html__( 'You selected %1$s %2$s but you didn\'t add it to the list before continuing to the next step.', 'polylang' ),
		'<span id="dialog-language-flag"></span>',
		'<strong id="dialog-language"></strong>'
	);
	?>
	</p>
	<p>
	<?php esc_html_e( 'Do you want to add this language before continuing to the next step?', 'polylang' ); ?>
	</p>
	<ul>
		<li>
			<?php
			printf(
				/* translators: %s: is the translated label of the 'Yes' button  */
				esc_html__( '%s: add this language and continue to the next step', 'polylang' ),
				'<strong>' . esc_html__( 'Yes', 'polylang' ) . '</strong >'
			);
			?>
		</li>
		<li>
		<?php
			printf(
				/* translators: %s: is the translated label of the 'No' button  */
				esc_html__( "%s: don't add this language and continue to the next step", 'polylang' ),
				'<strong>' . esc_html__( 'No', 'polylang' ) . '</strong >'
			);
			?>
		</li>
		<li>
		<?php
			printf(
				/* translators: %s: is the translated label of the 'Ignore' button  */
				esc_html__( '%s: stay at this step', 'polylang' ),
				'<strong>' . esc_html__( 'Ignore', 'polylang' ) . '</strong >'
			);
			?>
		</li>
	</ul>
</div>
