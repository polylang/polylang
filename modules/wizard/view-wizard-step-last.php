<?php
/**
 * Displays the wizard last step
 *
 * @package Polylang
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

?>
<h2><?php esc_html_e( "You're ready to translate your contents!", 'polylang' ); ?></h2>

<div class="documentation">
	<p><?php esc_html_e( "You're now able to translate your contents such as posts, pages, categories and tags. You can learn how to use Polylang by reading the documentation.", 'polylang' ); ?></p>
	<div class="documentation-container">
		<p class="pll-wizard-actions step documentation-button-container">
			<a
				class="button button-primary button-large documentation-button"
				href="<?php echo esc_url( 'https://polylang.pro/doc-category/getting-started/' ); ?>"
				target="blank"
			>
				<?php esc_html_e( 'Read documentation', 'polylang' ); ?>
			</a>
		</p>
	</div>
</div>

<ul class="pll-wizard-next-steps">
	<li class="pll-wizard-next-step-item">
		<div class="pll-wizard-next-step-description">
			<p class="next-step-heading"><?php esc_html_e( 'Next step', 'polylang' ); ?></p>
			<h3 class="next-step-description"><?php esc_html_e( 'Create menus', 'polylang' ); ?></h3>
			<p class="next-step-extra-info">
				<?php esc_html_e( 'To get your website ready, there are still two steps you need to perform manually: add menus in each language, and add a language switcher to allow your visitors to select their preferred language.', 'polylang' ); ?>
			</p>
		</div>
		<div class="pll-wizard-next-step-action">
			<p class="pll-wizard-actions step">
				<a class="button button-primary button-large" href="<?php echo esc_url( 'https://polylang.pro/doc/create-menus/' ); ?>">
					<?php esc_html_e( 'Read documentation', 'polylang' ); ?>
				</a>
			</p>
		</div>
	</li>
	<li class="pll-wizard-next-step-item">
		<div class="pll-wizard-next-step-description">
			<p class="next-step-heading"><?php esc_html_e( 'Next step', 'polylang' ); ?></p>
			<h3 class="next-step-description"><?php esc_html_e( 'Translate some pages', 'polylang' ); ?></h3>
			<p class="next-step-extra-info"><?php esc_html_e( "You're ready to translate the posts on your website.", 'polylang' ); ?></p>
		</div>
		<div class="pll-wizard-next-step-action">
			<p class="pll-wizard-actions step">
				<a class="button button-large" href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">
					<?php esc_html_e( 'View pages', 'polylang' ); ?>
				</a>
			</p>
		</div>
	</li>
	<?php if ( ! defined( 'POLYLANG_PRO' ) && ! defined( 'WOOCOMMERCE_VERSION' ) ) : ?>
		<li class="pll-wizard-next-step-item">
			<div class="pll-wizard-next-step-description">
				<p class="next-step-heading"><?php esc_html_e( 'Polylang Pro', 'polylang' ); ?></p>
				<h3 class="next-step-description"><?php esc_html_e( 'Upgrade to Polylang Pro', 'polylang' ); ?></h3>
				<p class="next-step-extra-info">
					<?php esc_html_e( 'Thank you for activating Polylang. If you want more advanced features - duplication, synchronization, REST API support, integration with other plugins, etc. - or further help provided by our Premium support, we recommend you upgrade to Polylang Pro.', 'polylang' ); ?>
				</p>
			</div>
			<div class="pll-wizard-next-step-action">
				<p class="pll-wizard-actions step">
					<a class="button button-primary button-large" href="<?php echo esc_url( 'https://polylang.pro/downloads/polylang-pro/' ); ?>">
						<?php esc_html_e( 'Buy now', 'polylang' ); ?>
					</a>
				</p>
			</div>
		</li>
	<?php endif; ?>
	<?php if ( ! defined( 'POLYLANG_PRO' ) && defined( 'WOOCOMMERCE_VERSION' ) && ! defined( 'PLLWC_VERSION' ) ) : ?>
		<li class="pll-wizard-next-step-item">
			<div class="pll-wizard-next-step-description">
				<p class="next-step-heading"><?php esc_html_e( 'WooCommerce', 'polylang' ); ?></p>
				<h3 class="next-step-description"><?php esc_html_e( 'Purchase Polylang Business Pack', 'polylang' ); ?></h3>
				<p class="next-step-extra-info">
					<?php
					printf(
						/* translators: %s is the name of Polylang Business Pack product */
						esc_html__( 'We have noticed that you are using Polylang with WooCommerce. To ensure a better compatibility, we recommend you use %s which includes both Polylang Pro and Polylang For WooCommerce.', 'polylang' ),
						'<strong>' . esc_html__( 'Polylang Business Pack', 'polylang' ) . '</strong>'
					);
					?>
				</p>
			</div>
			<div class="pll-wizard-next-step-action">
				<p class="pll-wizard-actions step">
					<a class="button button-primary button-large" href="<?php echo esc_url( 'https://polylang.pro/downloads/polylang-for-woocommerce/' ); ?>">
						<?php esc_html_e( 'Buy now', 'polylang' ); ?>
					</a>
				</p>
			</div>
		</li>
	<?php endif; ?>
	<li class="pll-wizard-additional-steps">
		<div class="pll-wizard-next-step-action">
			<p class="pll-wizard-actions step">
				<a class="button button-large" href="<?php echo esc_url( admin_url() ); ?>">
					<?php esc_html_e( 'Return to the Dashboard', 'polylang' ); ?>
				</a>
			</p>
		</div>
	</li>
</ul>
