/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is not neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ "use strict";
/******/ var __webpack_modules__ = ({

/***/ "./js/classic-editor.src.js":
/*!**********************************!*\
  !*** ./js/classic-editor.src.js ***!
  \**********************************/
/*! namespace exports */
/*! exports [not provided] [no usage info] */
/*! runtime requirements: __webpack_require__, __webpack_require__.r, __webpack_exports__, __webpack_require__.* */
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./metabox-translations.dep */ \"./js/metabox-translations.dep.js\");\n/**\n * @package Polylang\n */\n\n \n\n// tag suggest in metabox\njQuery(\n\tfunction( $ ) {\n\t\t$.ajaxPrefilter(\n\t\t\tfunction( options, originalOptions, jqXHR ) {\n\t\t\t\tvar lang = $( '.post_lang_choice' ).val();\n\t\t\t\tif ( 'string' === typeof options.data && -1 !== options.url.indexOf( 'action=ajax-tag-search' ) && lang ) {\n\t\t\t\t\toptions.data = 'lang=' + lang + '&' + options.data;\n\t\t\t\t}\n\t\t\t}\n\t\t);\n\t}\n);\n\n// overrides tagBox.get\njQuery(\n\tfunction( $ ) {\n\t\t// overrides function to add the language\n\t\ttagBox.get = function( id ) {\n\t\t\tvar tax = id.substr( id.indexOf( '-' ) + 1 );\n\n\t\t\t// add the language in the $_POST variable\n\t\t\tvar data = {\n\t\t\t\taction: 'get-tagcloud',\n\t\t\t\tlang:   $( '.post_lang_choice' ).val(),\n\t\t\t\ttax:    tax\n\t\t\t}\n\n\t\t\t$.post(\n\t\t\t\tajaxurl,\n\t\t\t\tdata,\n\t\t\t\tfunction( r, stat ) {\n\t\t\t\t\tif ( 0 == r || 'success' != stat ) {\n\t\t\t\t\t\tr = wpAjax.broken;\n\t\t\t\t\t}\n\n\t\t\t\t\t// @see code from WordPress core https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/js/tags-box.js#L291\n\t\t\t\t\t// @see wp_generate_tag_cloud function which generate the escaped HTML https://github.com/WordPress/WordPress/blob/a02b5cc2a8eecb8e076fbb7cf4de7bd2ec8a8eb1/wp-includes/category-template.php#L966-L975\n\t\t\t\t\tr = $( '<div />' ).addClass( 'the-tagcloud' ).attr( 'id', 'tagcloud-' + tax ).html( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t$( 'a', r ).click(\n\t\t\t\t\t\tfunction(){\n\t\t\t\t\t\t\ttagBox.flushTags( $( this ).closest( '.inside' ).children( '.tagsdiv' ), this );\n\t\t\t\t\t\t\treturn false;\n\t\t\t\t\t\t}\n\t\t\t\t\t);\n\n\t\t\t\t\tvar tagCloud = $( '#tagcloud-' + tax );\n\t\t\t\t\t// add an if else condition to allow modifying the tags outputed when switching the language\n\t\t\t\t\tvar v = tagCloud.css( 'display' );\n\t\t\t\t\tif ( v ) {\n\t\t\t\t\t\t// See the comment above when r variable is created.\n\t\t\t\t\t\t$( '#tagcloud-' + tax ).replaceWith( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith\n\t\t\t\t\t\t$( '#tagcloud-' + tax ).css( 'display', v );\n\t\t\t\t\t}\n\t\t\t\t\telse {\n\t\t\t\t\t\t// See the comment above when r variable is created.\n\t\t\t\t\t\t$( '#' + id ).after( r ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.after\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t);\n\t\t}\n\t}\n);\n\njQuery(\n\tfunction ( $ ) {\n\t\t// collect taxonomies - code partly copied from WordPress\n\t\tvar taxonomies = new Array();\n\t\t$( '.categorydiv' ).each(\n\t\t\tfunction(){\n\t\t\t\tvar this_id = $( this ).attr( 'id' ), taxonomyParts, taxonomy;\n\n\t\t\t\ttaxonomyParts = this_id.split( '-' );\n\t\t\t\ttaxonomyParts.shift();\n\t\t\t\ttaxonomy = taxonomyParts.join( '-' );\n\t\t\t\ttaxonomies.push( taxonomy ); // store the taxonomy for future use\n\n\t\t\t\t// add our hidden field in the new category form - for each hierarchical taxonomy\n\t\t\t\t// to set the language when creating a new category\n\t\t\t\t// html code inserted come from html code itself.\n\t\t\t\t$( '#' + taxonomy + '-add-submit' ).before( // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.before\n\t\t\t\t\t$( '<input />' ).attr( 'type', 'hidden' )\n\t\t\t\t\t\t.attr( 'id', taxonomy + '-lang' )\n\t\t\t\t\t\t.attr( 'name', 'term_lang_choice' )\n\t\t\t\t\t\t.attr( 'value', $( '.post_lang_choice' ).val() )\n\t\t\t\t);\n\t\t\t}\n\t\t);\n\n\t\t// ajax for changing the post's language in the languages metabox\n\t\t$( '.post_lang_choice' ).change(\n\t\t\tfunction() {\n\t\t\t\tvar value = $( this ).val();\n\t\t\t\tvar lang  = $( this ).children( 'option[value=\"' + value + '\"]' ).attr( 'lang' );\n\t\t\t\tvar dir   = $( '.pll-translation-column > span[lang=\"' + lang + '\"]' ).attr( 'dir' );\n\n\t\t\t\tvar data = {\n\t\t\t\t\taction:     'post_lang_choice',\n\t\t\t\t\tlang:       value,\n\t\t\t\t\tpost_type:  $( '#post_type' ).val(),\n\t\t\t\t\ttaxonomies: taxonomies,\n\t\t\t\t\tpost_id:    $( '#post_ID' ).val(),\n\t\t\t\t\t_pll_nonce: $( '#_pll_nonce' ).val()\n\t\t\t\t}\n\n\t\t\t\t$.post(\n\t\t\t\t\tajaxurl,\n\t\t\t\t\tdata,\n\t\t\t\t\tfunction( response ) {\n\t\t\t\t\t\tvar res = wpAjax.parseAjaxResponse( response, 'ajax-response' );\n\t\t\t\t\t\t$.each(\n\t\t\t\t\t\t\tres.responses,\n\t\t\t\t\t\t\tfunction() {\n\t\t\t\t\t\t\t\tswitch ( this.what ) {\n\t\t\t\t\t\t\t\t\tcase 'translations': // translations fields\n\t\t\t\t\t\t\t\t\t\t// Data is built and come from server side and is well escaped when necessary\n\t\t\t\t\t\t\t\t\t\t$( '.translations' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\t\t(0,_metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__.init_translations)();\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t\tcase 'taxonomy': // categories metabox for posts\n\t\t\t\t\t\t\t\t\t\tvar tax = this.data;\n\t\t\t\t\t\t\t\t\t\t// @see wp_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L175\n\t\t\t\t\t\t\t\t\t\t// @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/class-walker-category-checklist.php#L89-L111\n\t\t\t\t\t\t\t\t\t\t$( '#' + tax + 'checklist' ).html( this.supplemental.all ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\t\t// @see wp_popular_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L236\n\t\t\t\t\t\t\t\t\t\t$( '#' + tax + 'checklist-pop' ).html( this.supplemental.populars ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\t\t// @see wp_dropdown_categories https://github.com/WordPress/WordPress/blob/5.5.1/wp-includes/category-template.php#L336\n\t\t\t\t\t\t\t\t\t\t// which is called by PLL_Admin_Classic_Editor::post_lang_choice to generate supplemental.dropdown\n\t\t\t\t\t\t\t\t\t\t$( '#new' + tax + '_parent' ).replaceWith( this.supplemental.dropdown ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith\n\t\t\t\t\t\t\t\t\t\t$( '#' + tax + '-lang' ).val( $( '.post_lang_choice' ).val() ); // hidden field\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t\tcase 'pages': // parent dropdown list for pages\n\t\t\t\t\t\t\t\t\t\t// @see wp_dropdown_pages https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/post-template.php#L1186-L1208\n\t\t\t\t\t\t\t\t\t\t// @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/class-walker-page-dropdown.php#L88\n\t\t\t\t\t\t\t\t\t\t$( '#parent_id' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t\tcase 'flag': // flag in front of the select dropdown\n\t\t\t\t\t\t\t\t\t\t// Data is built and come from server side and is well escaped when necessary\n\t\t\t\t\t\t\t\t\t\t$( '.pll-select-flag' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t\tcase 'permalink': // Sample permalink\n\t\t\t\t\t\t\t\t\t\tvar div = $( '#edit-slug-box' );\n\t\t\t\t\t\t\t\t\t\tif ( '-1' != this.data && div.children().length ) {\n\t\t\t\t\t\t\t\t\t\t\t// @see get_sample_permalink_html https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/post.php#L1425-L1454\n\t\t\t\t\t\t\t\t\t\t\tdiv.html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t);\n\n\t\t\t\t\t\t// modifies the language in the tag cloud\n\t\t\t\t\t\t$( '.tagcloud-link' ).each(\n\t\t\t\t\t\t\tfunction() {\n\t\t\t\t\t\t\t\tvar id = $( this ).attr( 'id' );\n\t\t\t\t\t\t\t\ttagBox.get( id );\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t);\n\n\t\t\t\t\t\t// Modifies the text direction\n\t\t\t\t\t\t$( 'body' ).removeClass( 'pll-dir-rtl' ).removeClass( 'pll-dir-ltr' ).addClass( 'pll-dir-' + dir );\n\t\t\t\t\t\t$( '#content_ifr' ).contents().find( 'html' ).attr( 'lang', lang ).attr( 'dir', dir );\n\t\t\t\t\t\t$( '#content_ifr' ).contents().find( 'body' ).attr( 'dir', dir );\n\t\t\t\t\t}\n\t\t\t\t);\n\t\t\t}\n\t\t);\n\n\t\t// translations autocomplete input box\n\t\t(0,_metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__.init_translations)( $ );\n\t}\n);\n\n\n//# sourceURL=webpack://polylang/./js/classic-editor.src.js?");

/***/ }),

/***/ "./js/metabox-translations.dep.js":
/*!****************************************!*\
  !*** ./js/metabox-translations.dep.js ***!
  \****************************************/
/*! namespace exports */
/*! export init_translations [provided] [no usage info] [missing usage info prevents renaming] */
/*! other exports [not provided] [no usage info] */
/*! runtime requirements: __webpack_require__.r, __webpack_exports__, __webpack_require__.d, __webpack_require__.* */
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"init_translations\": () => /* binding */ init_translations\n/* harmony export */ });\nfunction init_translations( $ ) {\n\t$( '.tr_lang' ).each(\n\t\tfunction(){\n\t\t\tvar tr_lang = $( this ).attr( 'id' ).substring( 8 );\n\t\t\tvar td = $( this ).parent().parent().siblings( '.pll-edit-column' );\n\n\t\t\t$( this ).autocomplete(\n\t\t\t\t{\n\t\t\t\t\tminLength: 0,\n\t\t\t\t\tsource: ajaxurl + '?action=pll_posts_not_translated' +\n\t\t\t\t\t\t'&post_language=' + $( '.post_lang_choice' ).val() +\n\t\t\t\t\t\t'&translation_language=' + tr_lang +\n\t\t\t\t\t\t'&post_type=' + $( '#post_type' ).val() +\n\t\t\t\t\t\t'&_pll_nonce=' + $( '#_pll_nonce' ).val(),\n\t\t\t\t\tselect: function( event, ui ) {\n\t\t\t\t\t\t$( '#htr_lang_' + tr_lang ).val( ui.item.id );\n\t\t\t\t\t\t// ui.item.link is built and come from server side and is well escaped when necessary\n\t\t\t\t\t\ttd.html( ui.item.link ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t},\n\t\t\t\t}\n\t\t\t);\n\n\t\t\t// when the input box is emptied\n\t\t\t$( this ).on(\n\t\t\t\t'blur',\n\t\t\t\tfunction() {\n\t\t\t\t\tif ( ! $( this ).val() ) {\n\t\t\t\t\t\t$( '#htr_lang_' + tr_lang ).val( 0 );\n\t\t\t\t\t\t// Value is retrieved from HTML already generated server side\n\t\t\t\t\t\ttd.html( td.siblings( '.hidden' ).children().clone() ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t);\n\t\t}\n\t);\n}\n\n\n\n//# sourceURL=webpack://polylang/./js/metabox-translations.dep.js?");

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	if(__webpack_module_cache__[moduleId]) {
/******/ 		return __webpack_module_cache__[moduleId].exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/define property getters */
/******/ (() => {
/******/ 	// define getter functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/hasOwnProperty shorthand */
/******/ (() => {
/******/ 	__webpack_require__.o = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop)
/******/ })();
/******/ 
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
/******/ // startup
/******/ // Load entry module
/******/ __webpack_require__("./js/classic-editor.src.js");
/******/ // This entry module used 'exports' so it can't be inlined
