<?php
declare(strict_types=1);

namespace WP_Rocket\Engine\Optimization\RUCSS\Admin;

use WP_Rocket\Admin\Options_Data;
use WP_Rocket\Engine\Admin\Settings\Settings as AdminSettings;

class Settings {
	/**
	 * Instance of options handler.
	 *
	 * @var Options_Data
	 */
	private $options;

	/**
	 * Creates an instance of the class.
	 *
	 * @param Options_Data $options WP Rocket Options instance.
	 */
	public function __construct( Options_Data $options ) {
		$this->options = $options;
	}

	/**
	 * Add the RUCSS options to the WP Rocket options array
	 *
	 * @since 3.9
	 *
	 * @param array $options WP Rocket options array.
	 *
	 * @return array
	 */
	public function add_options( $options ) : array {
		$options = (array) $options;

		$options['remove_unused_css']          = 0;
		$options['remove_unused_css_safelist'] = [];

		return $options;
	}

	/**
	 * Determines if Remove Unused CSS option is enabled.
	 *
	 * @since 3.9
	 *
	 * @return boolean
	 */
	public function is_enabled() : bool {
		return (bool) $this->options->get( 'remove_unused_css', 0 );
	}

	/**
	 * Sanitizes RUCSS options values when the settings form is submitted
	 *
	 * @since 3.9
	 *
	 * @param array         $input    Array of values submitted from the form.
	 * @param AdminSettings $settings Settings class instance.
	 *
	 * @return array
	 */
	public function sanitize_options( array $input, AdminSettings $settings ) : array {
		$input['remove_unused_css']          = $settings->sanitize_checkbox( $input, 'remove_unused_css' );
		$input['remove_unused_css_safelist'] = ! empty( $input['remove_unused_css_safelist'] ) ? rocket_sanitize_textarea_field( 'remove_unused_css_safelist', $input['remove_unused_css_safelist'] ) : [];

		return $input;
	}

	/**
	 * Add Clean used CSS link to WP Rocket admin bar item
	 *
	 * @since 3.9
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
	 *
	 * @return void
	 */
	public function add_clean_used_css_menu_item( $wp_admin_bar ) {
		if ( ! current_user_can( 'rocket_remove_unused_css' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		$referer = '';
		$action  = 'rocket_clear_usedcss';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$referer_url = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_URL );
			$referer     = '&_wp_http_referer=' . rawurlencode( remove_query_arg( 'fl_builder', $referer_url ) );
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'wp-rocket',
				'id'     => 'clean-used-css',
				'title'  => __( 'Clear Used CSS', 'rocket' ),
				'href'   => wp_nonce_url( admin_url( "admin-post.php?action={$action}{$referer}" ), $action ),
			]
		);
	}
}
