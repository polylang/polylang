<?php

/**
 * Displays the wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$admin_status_report = Polylang_Woocommerce::instance()->admin_status_reports;

?>
<h2><?php esc_html_e( 'WooCommerce pages', 'polylang' ); ?></h2>
<?php
if ( count( $this->translation_updates ) > 0 ) {
	?>
	<p>
		<?php esc_html_e( 'To work correctly with WooCommerce we need that all the specific WooCommerce pages are created and translated.', 'polylang' ); ?><br />
		<?php if ( $admin_status_report->get_woocommerce_pages_status()->is_error ) : ?>
			<?php esc_html_e( 'First, before creating these pages in each language, we have to ensure that all the translation files are correctly installed.', 'polylang' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'All the specific WooCommerce pages have been correctly created and translated but some translation files have not been installed yet.', 'polylang' ); ?>
		<?php endif; ?>
	</p>
	<table id="translations-to-update" class="striped">
		<thead>
			<th colspan="2"><?php esc_html_e( 'Translations will be updated', 'polylang' ); ?></th>
		</thead>
		<tbody>
			<?php
			foreach ( $this->translation_updates as $translation ) {
				if ( 'plugin' !== $translation->type ) {
					continue;
				}
				$language_properties = $this->model->get_language( $translation->language );
				?>
				<tr>
					<td><?php echo esc_html( $translation->slug ); ?></td>
					<td><?php echo esc_html( $language_properties->name ) . '-' . esc_html( $translation->language ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php if ( $admin_status_report->get_woocommerce_pages_status()->is_error ) : ?>
	<p>
		<?php esc_html_e( 'Finally, we are going to ensure that all pages are correctly created and translated.', 'polylang' ); ?>
	</p>
	<?php endif; ?>
	<?php
} else {
	?>
	<p>
	<?php esc_html_e( 'To work correctly with WooCommerce we need that all specific WooCommerce pages are created and translated.', 'polylang' ); ?>
	<?php esc_html_e( 'We are going to ensure that all pages are correctly created and translated.', 'polylang' ); ?>
	</p>
	<?php
}
if ( $admin_status_report->get_woocommerce_pages_status()->is_error ) {
	$admin_status_report->wizard_status_report();
}
