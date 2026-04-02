<?php
/**
 * Installation and activation handler.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installation and activation handler class.
 */
class Install {

	/**
	 * Get plugin version from plugin file.
	 *
	 * @param string $plugin_file Path to the plugin file.
	 * @return string|null Plugin version or null if not found.
	 */
	private static function get_plugin_version( string $plugin_file ): ?string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! file_exists( $plugin_file ) ) {
			return null;
		}

		$plugin_data = get_plugin_data( $plugin_file, false, false );
		return $plugin_data['Version'] ?? null;
	}

	/**
	 * Check if required plugins meet minimum version requirements.
	 *
	 * @param bool $use_constants Whether to use defined constants (runtime) or read plugin files (activation).
	 * @return array Empty array if all requirements met, otherwise array of missing dependencies.
	 */
	public static function check_dependencies( bool $use_constants = true ): array {
		$missing_dependencies = array();

		// Check WooCommerce version.
		if ( $use_constants && defined( 'WC_VERSION' ) ) {
			$wc_version = WC_VERSION;
		} else {
			$wc_plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
			$wc_version     = self::get_plugin_version( $wc_plugin_file );
		}

		if ( ! $wc_version ) {
			$missing_dependencies[] = sprintf(
				/* translators: %s: minimum WooCommerce version */
				__( 'WooCommerce %s or higher is not installed.', 'another-blocks-for-dokan' ),
				ANOTHER_BLOCKS_DOKAN_MIN_WOOCOMMERCE_VERSION
			);
		} elseif ( version_compare( $wc_version, ANOTHER_BLOCKS_DOKAN_MIN_WOOCOMMERCE_VERSION, '<' ) ) {
			$missing_dependencies[] = sprintf(
				/* translators: 1: installed version, 2: minimum required version */
				__( 'WooCommerce %1$s is installed, but version %2$s or higher is required.', 'another-blocks-for-dokan' ),
				$wc_version,
				ANOTHER_BLOCKS_DOKAN_MIN_WOOCOMMERCE_VERSION
			);
		}

		// Check Dokan version.
		if ( $use_constants && defined( 'DOKAN_PLUGIN_VERSION' ) ) {
			$dokan_version = DOKAN_PLUGIN_VERSION;
		} else {
			$dokan_plugin_file = WP_PLUGIN_DIR . '/dokan-lite/dokan.php';
			$dokan_version     = self::get_plugin_version( $dokan_plugin_file );
		}

		if ( ! $dokan_version ) {
			$missing_dependencies[] = sprintf(
				/* translators: %s: minimum Dokan version */
				__( 'Dokan Lite %s or higher is not installed.', 'another-blocks-for-dokan' ),
				ANOTHER_BLOCKS_DOKAN_MIN_DOKAN_VERSION
			);
		} elseif ( version_compare( $dokan_version, ANOTHER_BLOCKS_DOKAN_MIN_DOKAN_VERSION, '<' ) ) {
			$missing_dependencies[] = sprintf(
				/* translators: 1: installed version, 2: minimum required version */
				__( 'Dokan Lite %1$s is installed, but version %2$s or higher is required.', 'another-blocks-for-dokan' ),
				$dokan_version,
				ANOTHER_BLOCKS_DOKAN_MIN_DOKAN_VERSION
			);
		}

		return $missing_dependencies;
	}

	/**
	 * Activation hook callback to check dependencies.
	 * Prevents plugin activation if dependencies are not met.
	 *
	 * @return void
	 */
	public static function activation_check(): void {
		// During activation, read plugin files directly as constants may not be defined yet.
		$missing_dependencies = self::check_dependencies( false );

		if ( ! empty( $missing_dependencies ) ) {
			// Deactivate the plugin immediately.
			deactivate_plugins( ANOTHER_BLOCKS_DOKAN_PLUGIN_BASENAME );

			$error_message  = '<h3>' . esc_html__( 'Another Blocks for Dokan - Activation Failed', 'another-blocks-for-dokan' ) . '</h3>';
			$error_message .= '<p>' . esc_html__( 'The following requirements are not met:', 'another-blocks-for-dokan' ) . '</p>';
			$error_message .= '<ul style="list-style: disc; padding-left: 20px;">';
			foreach ( $missing_dependencies as $dependency ) {
				$error_message .= '<li>' . esc_html( $dependency ) . '</li>';
			}
			$error_message .= '</ul>';
			$error_message .= '<p>' . esc_html__( 'Please install and activate the required plugins, then try again.', 'another-blocks-for-dokan' ) . '</p>';

			wp_die(
				wp_kses_post( $error_message ),
				esc_html__( 'Plugin Activation Failed', 'another-blocks-for-dokan' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Runtime dependency check and admin notice.
	 * Shows admin notice if dependencies are not met after activation.
	 *
	 * @return bool True if all requirements met, false otherwise.
	 */
	public static function runtime_check(): bool {
		$runtime_missing_dependencies = self::check_dependencies();

		if ( ! empty( $runtime_missing_dependencies ) ) {
			add_action(
				'admin_notices',
				function () use ( $runtime_missing_dependencies ) {
					?>
					<div class="notice notice-error">
						<p><strong><?php echo esc_html__( 'Another Blocks for Dokan is disabled.', 'another-blocks-for-dokan' ); ?></strong></p>
						<p><?php echo esc_html__( 'The following requirements are not met:', 'another-blocks-for-dokan' ); ?></p>
						<ul style="list-style: disc; padding-left: 20px;">
							<?php foreach ( $runtime_missing_dependencies as $dependency ) : ?>
								<li><?php echo esc_html( $dependency ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
				}
			);
			return false;
		}

		return true;
	}
}