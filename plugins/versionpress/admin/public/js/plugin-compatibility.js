/**
 * Asks for confirmation before installing or activating a plugin that is untested with or
 * incompatible with VersionPress. Labels are added by VersionPress\Admin\PluginCompatibility.
 */
( function () {
	const { __, sprintf } = wp.i18n;

	function confirmPlugin( label ) {
		const pluginName = label.dataset.pluginName;
		const message = label.classList.contains( 'vp-incompatible' )
			? sprintf(
					/* translators: %s: plugin name */
					__(
						'%s is not compatible with VersionPress. These plugins will not work correctly when used together. Continue anyway?',
						'versionpress'
					),
					pluginName
			  )
			: sprintf(
					/* translators: %s: plugin name */
					__(
						'%s was not tested with VersionPress yet. Some functionality may not work as intended. Continue anyway?',
						'versionpress'
					),
					pluginName
			  );

		// eslint-disable-next-line no-alert
		return window.confirm( message );
	}

	// Capturing listener: runs before WordPress' own handlers, e.g. the AJAX "Install Now".
	document.addEventListener(
		'click',
		( event ) => {
			const button = event.target.closest(
				'.plugin-card .install-now, .plugins .activate a'
			);
			if ( ! button ) {
				return;
			}

			const container = button.closest( '.plugin-card, tr' );
			const label = container?.querySelector(
				'.vp-compatibility.vp-incompatible, .vp-compatibility.vp-untested'
			);
			if ( label && ! confirmPlugin( label ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		},
		true
	);
} )();
