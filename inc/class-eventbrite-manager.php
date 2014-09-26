<?php
/**
 * Eventbrite Manager class for handling calls to the Eventbrite API.
 *
 * @package Eventbrite_API
 */

class Eventbrite_Manager {
	/**
	 * Stores our single instance.
	 */
	private static $instance;

	/**
	 * Return our instance, creating a new one if necessary.
	 *
	 * @uses Eventbrite_Manager::$instance
	 * @return object Eventbrite_Manager
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Eventbrite_Manager;
		}

		return self::$instance;
	}

	/**
	 * Make a call to the Eventbrite v3 REST API, or return an existing transient.
	 *
	 * @uses Eventbrite_Manager::$instance
	 * @return object Eventbrite_Manager
	 */
	public function request( $endpoint, $params = array(), $force = false ) {
		// Ensure it's an existing endpoint.
		if ( ! $this->validate_endpoint( $endpoint ) ) {
			return false;
		}

		// Make sure the parameters are valid for the endpoint.
		if ( ! $this->validate_request_params( $params, $endpoint ) ) {
			return false;
		}

		// Return a cached result if we have one.
		$cached = $this->get_cache( $endpoint, $params );
		if ( $cached && true != $force ) {
			return $cached;
		}

		// Make a fresh request and cache it.
		$request = Eventbrite_API::call( $endpoint, $params );
		set_transient( $this->get_transient_name( $endpoint, $params ), $request, WEEK_IN_SECONDS );
		
		return $request;
	}

	/**
	 * Verify the endpoint passed is valid.
	 *
	 * @return bool True if the endpoint is valid, false otherwise.
	 */
	public function validate_endpoint( $endpoint ) {
		return in_array( $endpoint, $this->get_endpoints() );
	}

	/**
	 * Validate the given parameters against its endpoint.
	 *
	 * @uses Eventbrite_Manager::$instance
	 * @return object Eventbrite_Manager
	 */
	public function validate_request_params( $params, $endpoint ) {
		// Check that an array was passed.
		if ( ! is_array( $params ) ) {
			return false;
		}

		switch ( $endpoint ) {
			// case 'value':
			// 	# code...
			// 	break;
			
			default:
				// The user_owned_events endpoint.
				$valid_params = array(
					'status' => array(
						'all',
						'draft',
						'live',
						'cancelled',
						'started',
						'ended',
					),
					'orderby' => array(
						'start_asc',
						'start_desc',
						'created_asc',
						'created_desc',
					),
				);
				break;
		}

		// kwight: sort this out
		return true;

		foreach ( $params as $param => $value ) {
			
		}
	}

	/**
	 * Get user-owned events.
	 *
	 * @uses Eventbrite_Manager::$instance
	 * @return object Eventbrite_Manager
	 */
	public function get_user_owned_events( $params = array(), $force = false ) {
		// Get the raw events.
		$events = $this->request( 'user_owned_events', $params, $force );

		// Bail if we get nothing back.
		if ( ! isset( $events ) || empty( $events->events ) ) {
			return array();
		}

		// kwight: Temp limit
		$events = array_slice( $events->events, 0, 3 );

		// Map our events to the format expected by Eventbrite_Post
		$events = array_map( array( $this, 'map_event_keys' ), $events );

		return $events;
 	}

	/**
	 * Get the transient for a certain endpoint and combination of parameters.
	 * get_transient() returns false if not found.
	 *
	 * @uses get_transient()
	 * @uses Eventbrite_Manager->get_transient_name()
	 * @return mixed Transient if found, false if not
	 */
	protected function get_cache( $endpoint, $params ) {
		return get_transient( $this->get_transient_name( $endpoint, $params ) ); 
	}

	/**
	 * Determine a transient's name based on endpoint and parameters.
	 *
	 * @return string
	 */
	protected function get_transient_name( $endpoint, $params ) {
		return 'eb_' . md5( $endpoint . implode( $params ) );
		//return 'eventbrite_' . $endpoint . implode( $params );
	}

	/**
	 * Return an array of valid Eventbrite API endpoints.
	 *
	 * @param
	 * @uses
	 * @return
	 */
	public function get_endpoints() {
		return array(
			// 'event_search',
			// 'event_categories',
			// 'event_details',
			// 'event_attendees',
			// 'event_attendees_detail',
			// 'event_orders',
			// 'event_discounts',
			// 'event_access_codes',
			// 'event_transfers',
			// 'event_teams',
			// 'event_teams_details',
			// 'event_teams_attendees',
			// 'user_details',
			// 'user_orders',
			'user_owned_events',
			// 'user_owned_events_orders',
			// 'user_owned_events_attendees',
			// 'user_venues',
			// 'user_organizers',
			// 'order_details',
			// 'contact_lists',
			// 'contact_list_details',
		);
	}

	/**
	 * Convert the Eventbrite API elements into elements used by Eventbrite_Post.
	 *
	 * @param
	 * @uses
	 * @return
	 */
	function map_event_keys( $api_event ) {
		$event = array();

		$event['ID']           = ( isset( $api_event->id ) )                ? $api_event->id                : '';
		$event['post_title']   = ( isset( $api_event->name->text ) )        ? $api_event->name->text        : '';
		$event['post_content'] = ( isset( $api_event->description->html ) ) ? $api_event->description->html : '';
		$event['post_date']    = ( isset( $api_event->created ) )           ? $api_event->created           : '';
		$event['url']          = ( isset( $api_event->url ) )               ? $api_event->url               : '';
		$event['logo_url']     = ( isset( $api_event->logo_url ) )          ? $api_event->logo_url          : '';
		$event['post_status']  = ( isset( $api_event->status ) )            ? $api_event->status            : '';
		$event['start']        = ( isset( $api_event->start->utc ) )        ? $api_event->start->utc        : '';
		$event['end']          = ( isset( $api_event->end->utc ) )          ? $api_event->end->utc          : '';
		$event['post_author']  = ( isset( $api_event->organizer->name ) )   ? $api_event->organizer->name   : '';
		$event['organizer_id'] = ( isset( $api_event->organizer->id ) )     ? $api_event->organizer->id     : '';
		$event['venue']        = ( isset( $api_event->venue->name ) )       ? $api_event->venue->name       : '';
		$event['venue_id']     = ( isset( $api_event->venue->id ) )         ? $api_event->venue->id         : '';

		return (object) $event;
	}
}

/**
 * Allow themes and plugins to access Eventbrite_Manager methods and properties.
 *
 * @uses Eventbrite_Manager::instance()
 * @return object Eventbrite_Manager
 */
function eventbrite() {
	return Eventbrite_Manager::instance();
}