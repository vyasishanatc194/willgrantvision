<?php
/**
 * Factory class for the server side events
 *
 * @package Pixel Caffeine
 */

namespace PixelCaffeine\ServerSide;

use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\Util;
use PixelCaffeine\FB\User_Data_Factory;

/**
 * Class Event_Factory
 *
 * @package PixelCaffeine\ServerSide
 */
class Event_Factory {

	/**
	 * Create the event object instance
	 *
	 * @param Pixel_Event $event The event instance.
	 *
	 * @return Event
	 */
	public function create_event( Pixel_Event $event ) {
		$event = ( new Event() )
			->setEventId( $event->get_event_id() )
			->setEventName( $event->get_event_name() )
			->setEventTime( time() )
			->setEventSourceUrl( Util::getRequestUri() )
			->setActionSource( ActionSource::WEBSITE )
			->setUserData( User_Data_Factory::decorate_server_side( $event->get_user_data() ) )
			->setCustomData( $this->create_custom_data( $event->get_event_data() ) );

		return $event;
	}

	/**
	 * Create the CustomData instance from the key=>value array
	 *
	 * @param array $data key=>value of the event data.
	 *
	 * @return CustomData
	 */
	public function create_custom_data( $data ) {
		$custom_data = new CustomData( $data );

		$custom_properties = array_diff_key( $data, CustomData::attributeMap() );
		foreach ( $custom_properties as $key => $value ) {
			$custom_data->addCustomProperty( $key, $value );
		}

		return $custom_data;
	}

}
