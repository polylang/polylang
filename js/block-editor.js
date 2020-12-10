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

/***/ "./js/block-editor.src.js":
/*!********************************!*\
  !*** ./js/block-editor.src.js ***!
  \********************************/
/*! namespace exports */
/*! exports [not provided] [no usage info] */
/*! runtime requirements: __webpack_require__, __webpack_require__.r, __webpack_exports__, __webpack_require__.* */
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./metabox-translations.dep */ \"./js/metabox-translations.dep.js\");\n/**\n * @package Polylang\n */\n\n\n\n/**\n * Filter REST API requests to add the language in the request\n *\n * @since 2.5\n */\nwp.apiFetch.use(\n\tfunction( options, next ) {\n\t\t// If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.\n\t\tif ( 'undefined' === typeof options.url ) {\n\t\t\tif ( 'undefined' === typeof options.data || null === options.data ) {\n\t\t\t\t// GET\n\t\t\t\toptions.path += ( ( options.path.indexOf( '?' ) >= 0 ) ? '&lang=' : '?lang=' ) + getCurrentLanguage();\n\t\t\t} else {\n\t\t\t\t// PUT, POST\n\t\t\t\toptions.data.lang = getCurrentLanguage();\n\t\t\t}\n\t\t}\n\t\treturn next( options );\n\t}\n);\n\n/**\n * Get the language from the HTML form\n *\n * @since 2.5\n *\n * @return {Element.value}\n */\nfunction getCurrentLanguage() {\n\treturn document.querySelector( '[name=post_lang_choice]' ).value;\n}\n\n/**\n * Save post after lang choice is done and redirect to the same page for refreshing all the data\n *\n * @since 2.5\n */\njQuery(\n\tfunction( $ ) {\n\t\t// savePost after changing the post's language and reload page for refreshing post translated data\n\t\t$( '.post_lang_choice' ).on(\n\t\t\t'change',\n\t\t\tfunction() {\n\t\t\t\tconst select = wp.data.select;\n\t\t\t\tconst dispatch = wp.data.dispatch;\n\t\t\t\tconst subscribe = wp.data.subscribe;\n\n\t\t\t\tlet unsubscribe = null;\n\n\t\t\t\t// Listen if the savePost is done\n\t\t\t\tconst savePostIsDone = new Promise(\n\t\t\t\t\tfunction( resolve, reject ) {\n\t\t\t\t\t\tunsubscribe = subscribe(\n\t\t\t\t\t\t\tfunction() {\n\t\t\t\t\t\t\t\tconst isSavePostSucceeded = select( 'core/editor' ).didPostSaveRequestSucceed();\n\t\t\t\t\t\t\t\tconst isSavePostFailed = select( 'core/editor' ).didPostSaveRequestFail();\n\t\t\t\t\t\t\t\tif ( isSavePostSucceeded || isSavePostFailed ) {\n\t\t\t\t\t\t\t\t\tif ( isSavePostFailed ) {\n\t\t\t\t\t\t\t\t\t\treject();\n\t\t\t\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\t\t\t\tresolve();\n\t\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t);\n\t\t\t\t\t}\n\t\t\t\t);\n\n\t\t\t\t// Specific case for empty posts\n\t\t\t\tif ( location.pathname.match( /post-new.php/gi ) ) {\n\t\t\t\t\tconst title = select( 'core/editor' ).getEditedPostAttribute( 'title' );\n\t\t\t\t\tconst content = select( 'core/editor' ).getEditedPostAttribute( 'content' );\n\t\t\t\t\tconst excerpt = select( 'core/editor' ).getEditedPostAttribute( 'excerpt' );\n\t\t\t\t\tif ( '' === title && '' === content && '' === excerpt ) {\n\t\t\t\t\t\t// Change the new_lang parameter with the new language value for reloading the page\n\t\t\t\t\t\t// WPCS location.search is never written in the page, just used to relaoad page ( See line 94 ) with the right value of new_lang\n\t\t\t\t\t\t// new_lang input is controlled server side in PHP. The value come from the dropdown list of language returned and escaped server side\n\t\t\t\t\t\tif ( -1 != location.search.indexOf( 'new_lang' ) ) {\n\t\t\t\t\t\t\t// use regexp non capturing group to replace new_lang parameter no matter where it is and capture other parameters which can be behind it\n\t\t\t\t\t\t\twindow.location.search = window.location.search.replace( /(?:new_lang=[^&]*)(&)?(.*)/, 'new_lang=' + this.value + '$1$2' ); // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment\n\t\t\t\t\t\t} else {\n\t\t\t\t\t\t\twindow.location.search = window.location.search + ( ( -1 != window.location.search.indexOf( '?' ) ) ? '&' : '?' ) + 'new_lang=' + this.value; // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t}\n\n\t\t\t\t// For empty posts savePost does nothing\n\t\t\t\tdispatch( 'core/editor' ).savePost();\n\n\t\t\t\tsavePostIsDone.then(\n\t\t\t\t\tfunction() {\n\t\t\t\t\t\t// If the post is well saved, we can reload the page\n\t\t\t\t\t\tunsubscribe();\n\t\t\t\t\t\twindow.location.reload();\n\t\t\t\t\t},\n\t\t\t\t\tfunction() {\n\t\t\t\t\t\t// If the post save failed\n\t\t\t\t\t\tunsubscribe();\n\t\t\t\t\t}\n\t\t\t\t).catch(\n\t\t\t\t\tfunction() {\n\t\t\t\t\t\t// If an exception is thrown\n\t\t\t\t\t\tunsubscribe();\n\t\t\t\t\t}\n\t\t\t\t);\n\t\t\t}\n\t\t);\n\t}\n);\n\n/**\n * Handles internals of the metabox:\n * Language select, autocomplete input field.\n *\n * @since 1.5\n */\njQuery(\n\tfunction( $ ) {\n\t\t// Ajax for changing the post's language in the languages metabox\n\t\t$( '.post_lang_choice' ).on(\n\t\t\t'change',\n\t\t\tfunction() {\n\t\t\t\tvar data = {\n\t\t\t\t\taction:     'post_lang_choice',\n\t\t\t\t\tlang:       $( this ).val(),\n\t\t\t\t\tpost_type:  $( '#post_type' ).val(),\n\t\t\t\t\tpost_id:    $( '#post_ID' ).val(),\n\t\t\t\t\t_pll_nonce: $( '#_pll_nonce' ).val()\n\t\t\t\t}\n\n\t\t\t\t$.post(\n\t\t\t\t\tajaxurl,\n\t\t\t\t\tdata,\n\t\t\t\t\tfunction( response ) {\n\t\t\t\t\t\tvar res = wpAjax.parseAjaxResponse( response, 'ajax-response' );\n\t\t\t\t\t\t$.each(\n\t\t\t\t\t\t\tres.responses,\n\t\t\t\t\t\t\tfunction() {\n\t\t\t\t\t\t\t\tswitch ( this.what ) {\n\t\t\t\t\t\t\t\t\tcase 'translations': // Translations fields\n\t\t\t\t\t\t\t\t\t\t// Data is built and come from server side and is well escaped when necessary\n\t\t\t\t\t\t\t\t\t\t$( '.translations' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\t\t(0,_metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__.init_translations)();\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t\tcase 'flag': // Flag in front of the select dropdown\n\t\t\t\t\t\t\t\t\t\t// Data is built and come from server side and is well escaped when necessary\n\t\t\t\t\t\t\t\t\t\t$( '.pll-select-flag' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html\n\t\t\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t);\n\t\t\t\t\t}\n\t\t\t\t);\n\t\t\t}\n\t\t);\n\n\t\t// Translations autocomplete input box\n\t\t(0,_metabox_translations_dep__WEBPACK_IMPORTED_MODULE_0__.init_translations)( $ );\n\t}\n);\n\n\n//# sourceURL=webpack://polylang/./js/block-editor.src.js?");

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
/******/ __webpack_require__("./js/block-editor.src.js");
/******/ // This entry module used 'exports' so it can't be inlined
