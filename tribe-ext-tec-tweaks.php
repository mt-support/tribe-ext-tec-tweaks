<?php
/**
 * Plugin Name:       The Events Calendar Extension: Tweaks
 * Plugin URI:        https://theeventscalendar.com/extensions/tec-tweaks/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-tec-tweaks/
 * Description:       A combination of snippets and tweaks for The Events Calendar
 * Version:           1.0.0
 * Extension Class:   Tribe\Extensions\Tec_Tweaks\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-tec-tweaks
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Tec_Tweaks;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Events__REST__V1__Main;
use Tribe__Extension;

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( Main::class )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * @var Settings
		 */
		private $settings;

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $ecp_active = false;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main', '5.0' );
			add_action( 'tribe_plugins_loaded', [ $this, 'detect_tec_pro' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tec_pro() {
			/** @var Tribe__Dependency $dep */
			$dep = tribe( Tribe__Dependency::class );

			if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main', '5.0' );
				$this->ecp_active = true;
			}
		}

		/**
		 * Get this plugin's options prefix.
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * @see \Tribe\Extensions\Tec_Tweaks\Settings::set_options_prefix()
		 *
		 * @return string
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', 'tribe-ext-tec-tweaks' );
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain.
			load_plugin_textdomain( 'tribe-ext-tec-tweaks', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			if ( ! $this->is_using_compatible_view_version() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			$this->disable_latest_past_events();
			$this->hide_event_end_time();
			$this->hide_tooltip();
			$this->hide_past_events_in_month_view();
			$this->hide_event_time_in_month_view();
			$this->remove_archives_from_page_title();
			$this->show_past_events_in_reverse_order();
			$this->remove_links_from_events();
			$this->change_free_in_ticket_cost();
			$this->disable_tribe_rest_api();
			add_filter( 'tribe_get_events_link', [ $this, 'custom_all_events_url' ] );
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '7.0';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';
					$message .= sprintf(
					// Translators: 1: extension name, 2: required version.
						__(
							'%1$s requires PHP version %2$s or newer to work. Please contact your website host and inquire about updating PHP.',
							'tribe-ext-tec-tweaks'
						),
						$this->get_name(),
						$php_required_version
					);
					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );
					$message .= '</p>';
					tribe_notice( 'tribe-ext-tec-tweaks-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Check if we have the required TEC view. Admin notice if we don't and user should see it.
		 *
		 * @return bool
		 */
		private function is_using_compatible_view_version() {
			$view_required_version = 2;

			$meets_req = true;

			// Is V2 enabled?
			if (
				function_exists( 'tribe_events_views_v2_is_enabled' )
				&& ! empty( tribe_events_views_v2_is_enabled() )
			) {
				$is_v2 = true;
			} else {
				$is_v2 = false;
			}

			// V1 compatibility check.
			if (
				1 === $view_required_version
				&& $is_v2
			) {
				$meets_req = false;
			}

			// V2 compatibility check.
			if (
				2 === $view_required_version
				&& ! $is_v2
			) {
				$meets_req = false;
			}

			// Notice, if should be shown.
			if (
				! $meets_req
				&& is_admin()
				&& current_user_can( 'activate_plugins' )
			) {
				if ( 1 === $view_required_version ) {
					$view_name = _x( 'Legacy Views', 'name of view', 'tribe-ext-tec-tweaks' );
				} else {
					$view_name = _x( 'New (V2) Views', 'name of view', 'tribe-ext-tec-tweaks' );
				}

				$view_name = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit.php?page=tribe-common&tab=display&post_type=tribe_events' ) ),
					$view_name
				);

				// Translators: 1: Extension plugin name, 2: Name of required view, linked to Display tab.
				$message = sprintf(
					__(
						'%1$s requires the "%2$s" so this extension\'s code will not run until this requirement is met. You may want to deactivate this extension or visit its homepage to see if there are any updates available.',
						'tribe-ext-tec-tweaks'
					),
					$this->get_name(),
					$view_name
				);

				tribe_notice(
					'tribe-ext-tec-tweaks-view-mismatch',
					'<p>' . $message . '</p>',
					[ 'type' => 'error' ]
				);
			}

			return $meets_req;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					__NAMESPACE__ . '\\',
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			return $this->settings->get_all_options();
		}

		/**
		 * Disables the "Recent Past Events" block.
		 */
		public function disable_latest_past_events() {
			$days_to_show = (bool) $this->settings->get_option( 'disable_recent_past_events', false );

			if ( $days_to_show ) {
				add_filter( 'tribe_events_views_v2_show_latest_past_events_view', '__return_false' );
			}
		}

		/**
		 * Hides the event end time on several views.
		 */
		public function hide_event_end_time() {
			$views = (array) $this->settings->get_option( 'remove_event_end_time', [] );

			if ( empty ( $views ) ) {
				return;
			}

			// If there are any views checked, then run the filter.
			add_filter(
				'tribe_events_event_schedule_details_formatting',
				function ( $settings ) {
					$views = (array) $this->settings->get_option( 'remove_event_end_time', [] );

					foreach ( $views as $view ) {
						if (
							tribe_is_view( $view )
							|| $view === tribe_context()->get( 'view', false )
						) {
							$settings['show_end_time'] = false;

							// If we found the view we are on, no need to go any further.
							break;
						}
					}

					return $settings;
				}
			);
		}

		/**
		 * Hide the tooltip in month view.
		 */
		public function hide_tooltip() {
			$hide_tooltip = (bool) $this->settings->get_option( 'hide_tooltip', false );

			if ( $hide_tooltip ) {
				add_filter(
					'tribe_template_pre_html:events/v2/month/calendar-body/day/calendar-events/calendar-event/tooltip',
					'__return_false'
				);
			}
		}

		/**
		 * Hide past events in month view.
		 */
		public function hide_past_events_in_month_view() {
			$hide_past = (bool) $this->settings->get_option( 'hide_past_events_in_month_view', false );

			if ( $hide_past ) {
				add_action(
					'wp_head',
					function () {
						echo '<style id="tribe-ext-tec-tweaks-css-hide-past">.tribe-events-calendar-month__day--past .tribe-events-calendar-month__events{display: none;}</style>';
					}
				);
			}
		}

		/**
		 * Hide event times in month view.
		 */
		public function hide_event_time_in_month_view() {
			$hide_event_time_in_month_view = (bool) $this->settings->get_option(
				'hide_event_time_in_month_view',
				false
			);

			if ( $hide_event_time_in_month_view ) {
				add_action(
					'wp_head',
					function () {
						echo '<style id="tribe-ext-tec-tweaks-css-hide-event-time">.tribe-events-calendar-month__calendar-event-datetime{display: none;}</style>';
					}
				);
			}
		}

		/**
		 * Remove "Archives:" from the page titles.
		 * Some themes add that.
		 */
		public function remove_archives_from_page_title() {
			$remove_archives = (bool) $this->settings->get_option( 'remove_archives_from_page_title', false );

			if ( $remove_archives ) {
				add_filter(
					'get_the_archive_title',
					function ( $title ) {
						if ( is_post_type_archive( 'tribe_events' ) ) {
							$title = sprintf( __( '%s' ), post_type_archive_title( '', false ) );
						}

						return $title;
					}
				);
			}
		}

		/**
		 * Show past events in reverse order.
		 */
		public function show_past_events_in_reverse_order() {
			$show_past_events_in_reverse_order = (bool) $this->settings->get_option(
				'show_past_events_in_reverse_order',
				false
			);

			if ( $show_past_events_in_reverse_order ) {
				// Change List View to Past Event Reverse Chronological Order.
				add_filter(
					'tribe_events_views_v2_view_list_template_vars',
					[ $this, 'tribe_past_reverse_chronological_v2' ],
					100
				);

				add_filter(
					'tribe_events_views_v2_view_photo_template_vars',
					[ $this, 'tribe_past_reverse_chronological_v2' ],
					100
				);
			}
		}

		/**
		 * Show past events in reverse order.
		 *
		 * @param $template_vars
		 *
		 * @return mixed
		 */
		private function tribe_past_reverse_chronological_v2( $template_vars ) {
			if ( ! empty( $template_vars['is_past'] ) ) {
				$template_vars['events'] = array_reverse( $template_vars['events'] );
			}

			return $template_vars;
		}

		/**
		 * Remove links from event titles. Event titles will not be clickable.
		 */
		public function remove_links_from_events() {
			$remove_links_from_events_views = (array) $this->settings->get_option( 'remove_links_from_events', false );

			if ( ! empty ( $remove_links_from_events_views ) ) {
				add_action( 'wp_head', [ $this, 'remove_links_html' ] );
			}
		}

		/**
		 * Code for removing links from event titles.
		 */
		public function remove_links_html() {
			$classes = (array) $this->settings->get_option( 'remove_links_from_events', false );

			$html = "\n<style id='tribe-ext-tec-tweaks-css'>";
			foreach ( $classes as $class ) {
				$html .= "\n." . $class . ",";
			}
			// Remove last comma
			$html = substr( $html, 0, - 1 );
			$html .= "\n{ pointer-events: none; }\n";
			$html .= "</style>\n";

			echo $html;
		}

		/**
		 * Change "Free" in event cost to custom text.
		 */
		public function change_free_in_ticket_cost() {
			$free = $this->settings->get_option( 'change_free_in_ticket_cost', '0' );

			if (
				! empty ( $free )
				|| $free == '0'
			) {
				add_filter( 'gettext', [ $this, 'change_free_function' ], 20, 3 );
			}
		}

		/**
		 * Change "Free" in event cost to custom text.
		 *
		 * @param $translation
		 * @param $text
		 * @param $domain
		 *
		 * @return mixed
		 */
		public function change_free_function( $translation, $text, $domain ) {
			$free        = $this->settings->get_option( 'change_free_in_ticket_cost' );
			$custom_text = [ 'Free' => $free ];

			// If this text domain starts with "tribe-", "the-events-", or "event-" and we have replacement text
			if (
				0 === strpos( $domain, 'the-events-calendar' )
				&& array_key_exists( $translation, $custom_text )
			) {
				$translation = $custom_text[ $translation ];
			}

			return $translation;
		}

		/**
		 * Reads and returns the custom 'All Events' URL if it is set.
		 *
		 * @param $url
		 *
		 * @return mixed
		 */
		public function custom_all_events_url( $url ) {
			$custom_url = $this->settings->get_option( 'custom_all_events_url' );

			if ( ! empty ( $custom_url ) ) {
				$url = $custom_url;
			}

			return $url;
		}

		/**
		 * Disable REST API for The Events Calendar.
		 */
		public function disable_tribe_rest_api() {
			$disable_tribe_rest_api = (bool) $this->settings->get_option( 'disable_tribe_rest_api', false );

			if ( $disable_tribe_rest_api ) {
				add_action(
					'init',
					function () {
						/** @var Tribe__Events__REST__V1__Main $rest */
						$rest = tribe( 'tec.rest-v1.main' );

						remove_action( 'rest_api_init', [ $rest, 'register_endpoints' ] );
					}
				);
			}
		}

	} // end class
} // end if class_exists check
