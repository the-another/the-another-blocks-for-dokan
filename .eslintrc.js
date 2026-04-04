const defaultConfig = require( '@wordpress/scripts/config/.eslintrc' );

module.exports = {
	...defaultConfig,
	settings: {
		...( defaultConfig.settings || {} ),
		'import/resolver': {
			node: {
				extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
			},
		},
		// WordPress packages are provided as webpack externals at runtime.
		'import/core-modules': [
			'@wordpress/api-fetch',
			'@wordpress/block-editor',
			'@wordpress/blocks',
			'@wordpress/components',
			'@wordpress/compose',
			'@wordpress/data',
			'@wordpress/element',
			'@wordpress/i18n',
			'@wordpress/icons',
			'@wordpress/plugins',
			'@wordpress/server-side-render',
		],
	},
	rules: {
		...( defaultConfig.rules || {} ),
		// Experimental WordPress APIs are necessary for some block features.
		'@wordpress/no-unsafe-wp-apis': 'warn',
	},
};
