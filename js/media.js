/**
 * Interact with WordPress Media Library a,d Editor
 * 
 * @package Polylang
 */

/**
 * @since 3.0
 *
 * @namespace
 */
var media = _.extend(
	{}, /** @lends media.prototype */
	{
		/**
		 * TODO: Find a way to delete references to Attachments collections that are not used anywhere else.
		 *
		 * @type {wp.media.model.Attachments}
		 */
		attachmentsCollections : [],

		init() {
			// Substitute WordPress media query shortcut with our decorated function.
			wp.media.query = media.query
		},

		/**
		 * Imitates { @see wp.media.query } but log all Attachments collections created.
		 * 
		 * @param {Object} [props]
		 * @return {wp.media.model.Attachments}
		 */
		query: function( props ) {
			var attachments = media.query.delegate();

			media.attachmentsCollections.push( attachments );

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

/**
 * @since 3.0
 * 
 * @memberOf media
 */
media.query = _.extend(
	media.query, /** @lends media.query prototype */
	{
		/**
		 * @type Function References WordPress { @see wp.media.query } constructor
		 */
		delegate: wp.media.query
	}
)

export default media
