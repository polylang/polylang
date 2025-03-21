/**
 * @package Polylang
 */

// Translations autocomplete input box.
export function initMetaboxAutoComplete() {
	jQuery('.tr_lang').each(
		function () {
			var tr_lang = jQuery(this).attr('id').substring(8);
			var td = jQuery(this).parent().parent().siblings('.pll-edit-column');

			jQuery(this).autocomplete(
				{
					minLength: 0,
					source: ajaxurl + '?action=pll_posts_not_translated' +
						'&post_language=' + jQuery('.post_lang_choice').val() +
						'&translation_language=' + tr_lang +
						'&post_type=' + jQuery('#post_type').val() +
						'&_pll_nonce=' + jQuery('#_pll_nonce').val(),
					select: function (event, ui) {
						jQuery('#htr_lang_' + tr_lang).val(ui.item.id);
						// ui.item.link is built and come from server side and is well escaped when necessary
						td.html(ui.item.link); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					},
				}
			);

			// when the input box is emptied
			jQuery(this).on(
				'blur',
				function () {
					if ( ! jQuery(this).val()  ) {
						jQuery('#htr_lang_' + tr_lang).val(0);
						// Value is retrieved from HTML already generated server side
						td.html(td.siblings('.hidden').children().clone()); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					}
				}
			);
		}
	);
}
