<?php

namespace Tribe\Extensions\Tec_Tweaks;

use Tribe__Settings_Manager;
use Tribe__Settings_Tab;

if ( ! class_exists( Settings::class ) ) {
	/**
	 * Do the Settings.
	 */
	class Settings {

		/**
		 * The Settings Helper class.
		 *
		 * @var Settings_Helper
		 */
		protected $settings_helper;

		/**
		 * The prefix for our settings keys.
		 *
		 * @see get_options_prefix() Use this method to get this property's value.
		 *
		 * @var string
		 */
		private $options_prefix = '';

		/**
		 * @var Tribe__Settings_Tab
		 */
		private $settings_tab;

		/**
		 * Settings constructor.
		 *
		 * @param string $options_prefix Recommended: the plugin text domain, with hyphens converted to underscores.
		 */
		public function __construct( $options_prefix ) {
			$this->settings_helper = new Settings_Helper();

			$this->set_options_prefix( $options_prefix );

			add_action( 'admin_init', [ $this, 'add_settings_tab' ] );
		}

		/**
		 * Allow access to set the Settings Helper property.
		 *
		 * @see get_settings_helper()
		 *
		 * @param Settings_Helper $helper
		 *
		 * @return Settings_Helper
		 */
		public function set_settings_helper( Settings_Helper $helper ) {
			$this->settings_helper = $helper;

			return $this->get_settings_helper();
		}

		/**
		 * Allow access to get the Settings Helper property.
		 *
		 * @see set_settings_helper()
		 */
		public function get_settings_helper() {
			return $this->settings_helper;
		}

		/**
		 * Set the options prefix to be used for this extension's settings.
		 *
		 * Recommended: the plugin text domain, with hyphens converted to underscores.
		 * Is forced to end with a single underscore. All double-underscores are converted to single.
		 *
		 * @see get_options_prefix()
		 *
		 * @param string $options_prefix
		 *
		 */
		private function set_options_prefix( $options_prefix = '' ) {
			if ( empty( $opts_prefix ) ) {
				$opts_prefix = str_replace( '-', '_', 'tribe-ext-tec-tweaks' ); // The text domain.
			}

			$opts_prefix = $opts_prefix . '_';

			$this->options_prefix = str_replace( '__', '_', $opts_prefix );
		}

		/**
		 * Get this extension's options prefix.
		 *
		 * @see set_options_prefix()
		 *
		 * @return string
		 */
		public function get_options_prefix() {
			return $this->options_prefix;
		}

		/**
		 * Given an option key, get this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->get_option( 'a_setting' )`.
		 *
		 * @see tribe_get_option()
		 *
		 * @param string $default
		 *
		 * @param string $key
		 *
		 * @return mixed
		 */
		public function get_option( $key = '', $default = '' ) {
			$key = $this->sanitize_option_key( $key );

			return tribe_get_option( $key, $default );
		}

		/**
		 * Get an option key after ensuring it is appropriately prefixed.
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		private function sanitize_option_key( $key = '' ) {
			$prefix = $this->get_options_prefix();

			if ( 0 === strpos( $key, $prefix ) ) {
				$prefix = '';
			}

			return $prefix . $key;
		}

		/**
		 * Get an array of all of this extension's options without array keys having the redundant prefix.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$raw_options = $this->get_all_raw_options();

			$result = [];

			$prefix = $this->get_options_prefix();

			foreach ( $raw_options as $key => $value ) {
				$abbr_key            = str_replace( $prefix, '', $key );
				$result[ $abbr_key ] = $value;
			}

			return $result;
		}

		/**
		 * Get an array of all of this extension's raw options (i.e. the ones starting with its prefix).
		 *
		 * @return array
		 */
		public function get_all_raw_options() {
			$tribe_options = Tribe__Settings_Manager::get_options();

			if ( ! is_array( $tribe_options ) ) {
				return [];
			}

			$result = [];

			foreach ( $tribe_options as $key => $value ) {
				if ( 0 === strpos( $key, $this->get_options_prefix() ) ) {
					$result[ $key ] = $value;
				}
			}

			return $result;
		}

		/**
		 * Given an option key, delete this extension's option value.
		 *
		 * This automatically prepends this extension's option prefix so you can just do `$this->delete_option( 'a_setting' )`.
		 *
		 * @param string $key
		 *
		 * @return mixed
		 */
		public function delete_option( $key = '' ) {
			$key = $this->sanitize_option_key( $key );

			$options = Tribe__Settings_Manager::get_options();

			unset( $options[ $key ] );

			return Tribe__Settings_Manager::set_options( $options );
		}

		/**
		 * Add the options prefix to each of the array keys.
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		private function prefix_settings_field_keys( array $fields ) {
			$prefixed_fields = array_combine(
				array_map(
					function ( $key ) {
						return $this->get_options_prefix() . $key;
					},
					array_keys( $fields )
				),
				$fields
			);

			return (array) $prefixed_fields;
		}

		/**
		 * Here is an example of getting some HTML for the Settings Header.
		 *
		 * @return string
		 */
		private function get_tweaks_intro_text() {
			$result = '<h3>' . esc_html_x(
					'The Events Calendar Tweaks',
					'Settings header',
					'tribe-ext-tec-tweaks'
				) . '</h3>';
			$result .= '<div style="margin-left: 20px;">';
			$result .= '<p>';
			$result .= esc_html_x(
				'This is a collection of tweaks and snippets for The Events Calendar.',
				'Settings intro',
				'tribe-ext-tec-tweaks'
			);
			$result .= ' ';
			$result .= sprintf(
				// Translators: 1: link to KB's snippets, 2: link icon.
				esc_html_x(
					'For more tweaks visit the %1$sSnippets%2$s section of our Knowledgebase.',
					'Settings intro',
					'tribe-ext-tec-tweaks'
				),
				'<a href="https://theeventscalendar.com/knowledgebase/knowledgebase-category/snippets/" target="_blank">',
				'</a><span class="dashicons dashicons-external"></span>'
			);
			$result .= '</p>';
			$result .= '</div>';

			return $result;
		}

		/**
		 * Setting up the Tweaks setting tab in admin.
		 */
		public function add_settings_tab() {
			$args = [
				'priority' => 110,
				'fields'   => $this->prefix_settings_field_keys( $this->get_settings_fields() ),
			];

			if ( empty ( $this->settings_tab ) ) {
				$this->settings_tab = new Tribe__Settings_Tab(
					'tec-tweaks',
					esc_html_x(
						'Tweaks',
						'settings tab name',
						'tribe-ext-tec-tweaks'
					),
					$args
				);
			}
		}

		/**
		 * Adds a new section of fields to Events > Settings > Tweaks tab.
		 *
		 * @return array[]
		 */

		public function get_settings_fields() {
			$remove_event_end_time_in_views = [
				'recent' => 'Recent past events list',
				'single' => 'Single event page',
				'day'    => 'Day view',
				'list'   => 'List view',
				'month'  => 'Month view tooltip',
			];

			$remove_links_from_events_views = [
				'tribe-events-calendar-day__event-title-link'            => 'Day view',
				'tribe-events-calendar-list__event-title-link'           => 'List view',
				'tribe-events-calendar-month__calendar-event-title-link' => 'Month view',
			];

			// IF ECP is active, show more options.
			if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
				$remove_event_end_time_in_views['week'] = 'Week view tooltip';

				$remove_links_from_events_views['tribe-events-pro-map__event-card-button']  = 'Map view';
				$remove_links_from_events_views['tribe-events-pro-photo__event-title-link'] = 'Photo view';
				$remove_links_from_events_views['tribe-events-pro-week-grid__event-link']   = 'Week view';
			}

			return [
				'Start'                             => [
					'type' => 'html',
					'html' => "<div class='tribe-settings-form-wrap'>",
				],
				'Example'                           => [
					'type' => 'html',
					'html' => $this->get_tweaks_intro_text(),
				],
				'disable_recent_past_events'        => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__( 'Disable "Recent Past Events"', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__(
						'When there are no events coming up in your calendar a list of recent past events will be shown. Checking this setting will remove that list.',
						'tribe-ext-tec-tweaks'
					),
					'validation_type' => 'boolean',
				],
				'remove_event_end_time'             => [
					'type'            => 'checkbox_list',
					'label'           => esc_html__( 'Remove event end time', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__(
							'When this box is checked the end time will no longer display for events that end on the same day when viewing the list, day, views, the recent past events list, the tooltip in month and week (Pro) views, as well as on the event page itself.',
							'tribe-ext-tec-tweaks'
						) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/remove-the-event-end-time-in-views/" target="_blank">Remove the Event End Time in Views</a>',
					'options'         => $remove_event_end_time_in_views,
					'validation_type' => 'options_multi',
					'can_be_empty'    => true,
				],
				'hide_tooltip'                      => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__( 'Hide tooltip in Month view', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( 'When this box is checked the tooltip will be removed from month view.' ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/hiding-tooltips-in-month-and-week-view/" target="_blank">Hiding Tooltips in Month and Week View</a>',
					'validation_type' => 'boolean',
				],
				'hide_past_events_in_month_view'    => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__( "Hide past events in Month view", 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( "Checking this box will hide past events in Month view." ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/hide-past-events-on-the-events-calendars-month-view/" target="_blank">Hide Past Events in Month View</a>',
					'validation_type' => 'boolean',
				],
				'hide_event_time_in_month_view'     => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__( "Hide event time in Month view", 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( "Checking this box will hide the start and end time of the events in Month view." ),
					'validation_type' => 'boolean',
				],
				'remove_archives_from_page_title'   => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__(
						"Remove 'Archives:' from the calendar page title",
						'tribe-ext-tec-tweaks'
					),
					'tooltip'         => esc_html__( "Checking this box will try to remove 'Archives:' from the calendar page title, which is usually coming from the page or archive template of the theme." ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/remove-archives-from-calendar-page-title/" target="_blank">Removing "Archives" From the Calendar Page Title</a>',
					'validation_type' => 'boolean',
				],
				'show_past_events_in_reverse_order' => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__( "Show past events in reverse order", 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( "The calendar's list and photo (Pro) views show past events in chronological order by default. That means the oldest events are displayed first and get newer as you go. Check this checkbox if you would like to show the events in reverse order, where the newest events are displayed first." ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/showing-past-events-in-reverse-order/" target="_blank">Showing Past Events in Reverse Order</a>',
					'validation_type' => 'boolean',
				],
				'remove_links_from_events'          => [
					'type'            => 'checkbox_list',
					'label'           => esc_html__( "Remove links pointing to events", 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( "This will remove the links from events so that users cannot click on them. This way, users cannot visit single events pages." ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/remove-links-from-events/" target="_blank">Remove Links from Events</a>',
					'options'         => $remove_links_from_events_views,
					'validation_type' => 'options_multi',
					'can_be_empty'    => true,
				],
				'change_free_in_ticket_cost'        => [
					'type'            => 'text',
					'label'           => esc_html__( 'Change "Free" in the ticket cost', 'tribe-ext-tec-tweaks' ),
					'tooltip'         => esc_html__( "When you enter a price or a price range for an event and it is or starts with zero (0), then on the front-end it will show up as set above. Leave empty for default setting." ) . '<br>' . esc_html__(
							'Source:',
							'tribe-ext-tec-tweaks'
						) . ' <a href="https://theeventscalendar.com/knowledgebase/k/changing-the-free-event-price-to-0-zero/" target="_blank">Change “Free” to “0” in the Ticket Cost</a>',
					'validation_type' => 'html',
					'can_be_empty'    => true,
				],
				'custom_all_events_url'             => [
					'type'            => 'text',
					'label'           => esc_html__( 'Custom URL for "All Events"', 'tribe-ext-custom-all-events-url' ),
					'tooltip'         => sprintf(
						esc_html__(
							'Enter your custom URL, including "http://" or "https://", for example %s. This can be useful when your main calendar page is not the default one.',
							'tribe-ext-custom-all-events-url'
						),
						'<code>https://demo.theeventscalendar.com/events/</code>'
					),
					'validation_type' => 'html',
				],
				'disable_tribe_rest_api'            => [
					'type'            => 'checkbox_bool',
					'label'           => esc_html__(
						"Disable REST API for The Events Calendar",
						'tribe-ext-tec-tweaks'
					),
					'tooltip'         => esc_html__( "Checking this box will disable the REST API for The Events Calendar and its add-ons." ),
					'validation_type' => 'boolean',
				],
				'End'                               => [
					'type' => 'html',
					'html' => "</div>",
				],

			];
		}
	} // class
}
