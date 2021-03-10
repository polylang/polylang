/**
 * @package Polylang
 */

import {
	initializeLanguageOldValue,
	initializeConfimationModal
} from './lib/confirmation-modal';

// tag suggest in metabox
jQuery(
	function( $ ) {
		$.ajaxPrefilter(
			function( options, originalOptions, jqXHR ) {
				var lang = $( '.post_lang_choice' ).val();
				if ( 'string' === typeof options.data && -1 !== options.url.indexOf( 'action=ajax-tag-search' ) && lang ) {
					options.data = 'lang=' + lang + '&' + options.data;
				}
			}
		);
	}
);

// overrides tagBox.get
jQuery(
	function( $ ) {
		// overrides function to add the language
		tagBox.get = function( id ) {
			var tax = id.substr( id.indexOf( '-' ) + 1 );

			// add the language in the $_POST variable
			var data = {
				action: 'get-tagcloud',
				lang:   $( '.post_lang_choice' ).val(),
				tax:    tax
			}

			$.post(
				ajaxurl,
				data,
				function( r, stat ) {
					if ( 0 == r || 'success' != stat ) {
						r = wpAjax.broken;
					}

					// @see code from WordPress core https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/js/tags-box.js#L291
					// @see wp_generate_tag_cloud function which generate the escaped HTML https://github.com/WordPress/WordPress/blob/a02b5cc2a8eecb8e076fbb7cf4de7bd2ec8a8eb1/wp-includes/category-template.php#L966-L975
					r = $( '<div />' ).addClass( 'the-tagcloud' ).attr( 'id', 'tagcloud-' + tax ).html( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					$( 'a', r ).on(
						'click',
						function(){
							tagBox.flushTags( $( this ).closest( '.inside' ).children( '.tagsdiv' ), this );
							return false;
						}
					);

					var tagCloud = $( '#tagcloud-' + tax );
					// add an if else condition to allow modifying the tags outputed when switching the language
					var v = tagCloud.css( 'display' );
					if ( v ) {
						// See the comment above when r variable is created.
						$( '#tagcloud-' + tax ).replaceWith( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
						$( '#tagcloud-' + tax ).css( 'display', v );
					}
					else {
						// See the comment above when r variable is created.
						$( '#' + id ).after( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.after
					}
				}
			);
		}
	}
);

jQuery(
	function( $ ) {
		// collect taxonomies - code partly copied from WordPress
		var taxonomies = new Array();
		$( '.categorydiv' ).each(
			function(){
				var this_id = $( this ).attr( 'id' ), taxonomyParts, taxonomy;

				taxonomyParts = this_id.split( '-' );
				taxonomyParts.shift();
				taxonomy = taxonomyParts.join( '-' );
				taxonomies.push( taxonomy ); // store the taxonomy for future use

				// add our hidden field in the new category form - for each hierarchical taxonomy
				// to set the language when creating a new category
				// html code inserted come from html code itself.
				$( '#' + taxonomy + '-add-submit' ).before( // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.before
					$( '<input />' ).attr( 'type', 'hidden' )
						.attr( 'id', taxonomy + '-lang' )
						.attr( 'name', 'term_lang_choice' )
						.attr( 'value', $( '.post_lang_choice' ).val() )
				);
			}
		);

		// Initialize current language to be able to compare if it changes.
		initializeLanguageOldValue();

		// ajax for changing the post's language in the languages metabox
		$( '.post_lang_choice' ).on(
			'change',
			function( event ) {
				// Initialize the confirmation dialog box.
				const confirmationModal = initializeConfimationModal();
				const { dialogContainer: dialog } = confirmationModal;
				let { dialogResult } = confirmationModal;
				// The selected option in the dropdown list.
				const selectedOption = event.target;

				if ( $( this ).data( 'old-value' ) !== selectedOption.value && ! isEmptyPost() ) {
					dialog.dialog( 'open' );
				} else {
					dialogResult = Promise.resolve();
				}

				// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
				dialogResult.then(
					() => {
						var lang  = selectedOption.options[selectedOption.options.selectedIndex].lang; // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
						var dir   = $( '.pll-translation-column > span[lang="' + lang + '"]' ).attr( 'dir' ); // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent

						var data = {  // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
							action:     'post_lang_choice',
							lang:       selectedOption.value,
							post_type:  $( '#post_type' ).val(),
							taxonomies: taxonomies,
							post_id:    $( '#post_ID' ).val(),
							_pll_nonce: $( '#_pll_nonce' ).val()
						}

						$.post(
							ajaxurl,
							data,
							function( response ) {
								var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
								$.each(
									res.responses,
									function() {
										switch ( this.what ) {
											case 'translations': // translations fields
												// Data is built and come from server side and is well escaped when necessary
												$( '.translations' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
												init_translations();
											break;
											case 'taxonomy': // categories metabox for posts
												var tax = this.data;
												// @see wp_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L175
												// @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/class-walker-category-checklist.php#L89-L111
												$( '#' + tax + 'checklist' ).html( this.supplemental.all ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
												// @see wp_popular_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L236
												$( '#' + tax + 'checklist-pop' ).html( this.supplemental.populars ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
												// @see wp_dropdown_categories https://github.com/WordPress/WordPress/blob/5.5.1/wp-includes/category-template.php#L336
												// which is called by PLL_Admin_Classic_Editor::post_lang_choice to generate supplemental.dropdown
												$( '#new' + tax + '_parent' ).replaceWith( this.supplemental.dropdown ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
												$( '#' + tax + '-lang' ).val( $( '.post_lang_choice' ).val() ); // hidden field
											break;
											case 'pages': // parent dropdown list for pages
												// @see wp_dropdown_pages https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/post-template.php#L1186-L1208
												// @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/class-walker-page-dropdown.php#L88
												$( '#parent_id' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
											break;
											case 'flag': // flag in front of the select dropdown
												// Data is built and come from server side and is well escaped when necessary
												$( '.pll-select-flag' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
											break;
											case 'permalink': // Sample permalink
												var div = $( '#edit-slug-box' );
												if ( '-1' != this.data && div.children().length ) {
													// @see get_sample_permalink_html https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/post.php#L1425-L1454
													div.html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
												}
											break;
										}
									}
								);

								// Update the old language with the new one to be able to compare it in the next changing.
								initializeLanguageOldValue();
								// modifies the language in the tag cloud
								$( '.tagcloud-link' ).each(
									function() {
										var id = $( this ).attr( 'id' );
										tagBox.get( id );
									}
								);

								// Modifies the text direction
								$( 'body' ).removeClass( 'pll-dir-rtl' ).removeClass( 'pll-dir-ltr' ).addClass( 'pll-dir-' + dir );
								$( '#content_ifr' ).contents().find( 'html' ).attr( 'lang', lang ).attr( 'dir', dir );
								$( '#content_ifr' ).contents().find( 'body' ).attr( 'dir', dir );

								pll.media.resetAllAttachmentsCollections();
							}
						)
					},
					() => {} // Do nothing when promise is rejected by clicking the Cancel dialog button.
				);
				// phpcs:enable PEAR.Functions.FunctionCallSignature.EmptyLine

				function isEmptyPost() {
					const title = $( 'input#title' ).val();
					const content = $( 'textarea#content' ).val();
					const excerpt = $( 'textarea#excerpt' ).val();

					return ! title && ! content && ! excerpt;
				}
			}
		);

		// translations autocomplete input box
		function init_translations() {
			$( '.tr_lang' ).each(
				function(){
					var tr_lang = $( this ).attr( 'id' ).substring( 8 );
					var td = $( this ).parent().parent().siblings( '.pll-edit-column' );

					$( this ).autocomplete(
						{
							minLength: 0,
							source: ajaxurl + '?action=pll_posts_not_translated' +
								'&post_language=' + $( '.post_lang_choice' ).val() +
								'&translation_language=' + tr_lang +
								'&post_type=' + $( '#post_type' ).val() +
								'&_pll_nonce=' + $( '#_pll_nonce' ).val(),
							select: function( event, ui ) {
								$( '#htr_lang_' + tr_lang ).val( ui.item.id );
								// ui.item.link is built and come from server side and is well escaped when necessary
								td.html( ui.item.link ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
							},
						}
					);

					// when the input box is emptied
					$( this ).on(
						'blur',
						function() {
							if ( ! $( this ).val() ) {
								$( '#htr_lang_' + tr_lang ).val( 0 );
								// Value is retrieved from HTML already generated server side
								td.html( td.siblings( '.hidden' ).children().clone() ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
							}
						}
					);
				}
			);
		}

		init_translations();
	}
);

/**
 * @since 3.0
 *
 * @namespace pll
 */
var pll = window.pll || {};

/**
 * @since 3.0
 *
 * @namespace pll.media
 */
_.extend( pll, { media: {} } );

/**
 * @since 3.0
 *
 * @alias pll.media
 * @memberOf pll
 * @namespace
 */
var media = _.extend(
	pll.media, /** @lends pll.media.prototype */
	{
		/**
		 * TODO: Find a way to delete references to Attachments collections that are not used anywhere else.
		 *
		 * @type {wp.media.model.Attachments}
		 */
		attachmentsCollections : [],

		/**
		 * Imitates { @see wp.media.query } but log all Attachments collections created.
		 *
		 * @param {Object} [props]
		 * @return {wp.media.model.Attachments}
		 */
		query: function( props ) {
			var attachments = pll.media.query.delegate( props );

			pll.media.attachmentsCollections.push( attachments );

			return attachments;
		},

		resetAllAttachmentsCollections: function() {
			this.attachmentsCollections.forEach(
				function( attachmentsCollection ) {
					/**
					 * First reset the { @see wp.media.model.Attachments } collection.
					 * Then, if it is mirroring a { @see wp.media.model.Query } collection,
					 * refresh this one too, so it will fetch new data from the server,
					 * and then the wp.media.model.Attachments collection will syncrhonize with the new data.
					 */
					attachmentsCollection.reset();
					if (attachmentsCollection.mirroring) {
						attachmentsCollection.mirroring._hasMore = true;
						attachmentsCollection.mirroring.reset();
					}
				}
			);
		}
	}
);

if ( 'undefined' !== typeof wp && 'undefined' !== typeof wp.media ) {

	/**
	 * @since 3.0
	 *
	 * @memberOf pll.media
	 */
	media.query = _.extend(
		media.query, /** @lends pll.media.query prototype */
		{
			/**
			 * @type Function References WordPress { @see wp.media.query } constructor
			 */
			delegate: wp.media.query
		}
	)

	// Substitute WordPress media query shortcut with our decorated function.
	wp.media.query = media.query

}
