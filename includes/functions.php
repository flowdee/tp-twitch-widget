<?php
/**
 * Functions
 *
 * @since       2.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get options
 *
 * return array options or empty when not available
 */
function tp_twitch_get_options() {
    return get_option( 'tp_twitch', array() );
}

/**
 * Get single option (incl. default value)
 *
 * @param $key
 * @param string $default
 *
 * @return null|string
 */
function tp_twitch_get_option( $key, $default = '' ) {

	$options = tp_twitch_get_options();

	if ( isset( $options[$key] ) )
		return $options[$key];

	return ( $default ) ? $default : tp_twitch_get_option_default_value( $key );
}

/**
 * Get option default value
 *
 * @param $key
 *
 * @return null|string
 */
function tp_twitch_get_option_default_value( $key ) {

	switch ( $key ) {
		case 'cache_duration':
			$value = 6;
			break;
		case 'widget_size':
			$value = 'large';
			break;
		case 'widget_preview':
			$value = 'image';
			break;
		default:
			$value = null;
			break;
	}

	$value = apply_filters( 'tp_twitch_option_default_value', $value, $key );

	return $value;
}

/**
 * Delete cache
 */
function tp_twitch_delete_cache() {

	global $wpdb;

	$sql = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "%_transient_tp_twitch_%"';

	$wpdb->query( $sql );
}

/**
 * Delete streams cache
 */
function tp_twitch_delete_streams_cache() {

	global $wpdb;

	$sql = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "%_transient_tp_twitch_streams_%"';

	$wpdb->query( $sql );
}

/**
 * Get games data (either from cache or API)
 *
 * @return array
 */
function tp_twitch_get_games() {

	// Looking for cached data
	$games = get_transient( 'tp_twitch_games' );

	if ( $games )
		return $games;

	// Query API
	$args = array(
		'first' => 100
	);

	$games = tp_twitch_get_top_games_from_api( $args );

	if ( empty( $games ) )
		return null;

	$games_indexed = array(); // We need the game id to make them accessible

	foreach ( $games as $game ) {

		if ( ! isset( $game['id'] ) || ! isset( $game['name'] ) )
			continue;

		$games_indexed[$game['id']] = $game;
	}

	$games = $games_indexed;

	// Cache data
	set_transient( 'tp_twitch_games', $games, 7 * DAY_IN_SECONDS );

	// Return
	return $games;
}

/**
 * Get game by id
 *
 * @param $game_id
 *
 * @return mixed|null
 */
function tp_twitch_get_game_by_id( $game_id ) {

	if ( empty ( $game_id ) )
		return null;

	$games = tp_twitch_get_games();

	return ( isset ( $games[$game_id] ) ) ? $games[$game_id] : null;
}

/**
 * Get game options
 *
 * @return array
 */
function tp_twitch_get_game_options() {

	$games = tp_twitch_get_games();

	$options = array();

	if ( is_array( $games ) && sizeof ( $games ) > 0 ) {

		$options[0] = __( 'Please select...', 'tp-twitch-widget' );

		$games = tp_twitch_array_sort( $games, 'name' );

		foreach ( $games as $game ) {

			if ( ! isset( $game['id'] ) || ! isset( $game['name'] ) )
				continue;

			$options[$game['id']] = $game['name'];
		}
	} else {
		$options[0] = __( 'Please connect to API first...', 'tp-twitch-widget' );
	}

	return $options;
}

/**
 * Get languages
 *
 * Source #1: Twitch Language Selector
 * Source #2: https://gist.githubusercontent.com/DimazzzZ/4e2a5a6c8c6f67900091/raw/3dc51cb81ba4bb93c9e7ce7e9c4bb8abbd9ca782/iso-639-1-codes.php
 *
 * @return array
 */
function tp_twitch_get_languages() {

	return array(
		'da' => __('Danish', 'tp-twitch-game' ),
		'de' => __( 'German', 'tp-twitch-game' ),
		'en' => __( 'English', 'tp-twitch-game' ),
		'en-gb' => __('English (UK)', 'tp-twitch-game' ),
		'es' => __( 'Spanish', 'tp-twitch-game' ),
		'es-mx' => __( 'Spanish (Latin American)', 'tp-twitch-game' ),
		'fr' => __( 'French', 'tp-twitch-game' ),
		'it' => __( 'Italian', 'tp-twitch-game' ),
		'hu' => __( 'Hungarian', 'tp-twitch-game' ),
		'nl' => __( 'Dutch', 'tp-twitch-game' ),
		'no' => __( 'Norwegian', 'tp-twitch-game' ),
		'pl' => __( 'Polish', 'tp-twitch-game' ),
		'pt' => __( 'Portuguese', 'tp-twitch-game' ),
		'pt-br' => __( 'Portuguese (Brazil)', 'tp-twitch-game' ),
		'sk' => __( 'Slovenian', 'tp-twitch-game' ),
		'fi' => __( 'Finnish', 'tp-twitch-game' ),
		'sv' => __( 'Swedish', 'tp-twitch-game' ),
		'vi' => __( 'Vietnamese', 'tp-twitch-game' ),
		'tr' => __( 'Turkish', 'tp-twitch-game' ),
		'cs' => __( 'Czech', 'tp-twitch-game' ),
		'el' => __( 'Greek', 'tp-twitch-game' ),
		'bg' => __( 'Bulgarian', 'tp-twitch-game' ),
		'ru' => __( 'Russian', 'tp-twitch-game' ),
		'ar' => __( 'Arabic', 'tp-twitch-game' ),
		'th' => __( 'Thai', 'tp-twitch-game' ),
		'zh-cn' => __( 'Chinese', 'tp-twitch-game' ),
		'zh-tw' => __( 'Chinese (Traditional)', 'tp-twitch-game' ),
		'ja' => __( 'Japanese', 'tp-twitch-game' ),
		'ko' => __( 'Korean', 'tp-twitch-game' ),
		'hi' => __( 'Hindi', 'tp-twitch-game' ),
		'ro' => __( 'Romanian', 'tp-twitch-game' ),
	);
}

/**
 * Get language options
 *
 * @return array
 */
function tp_twitch_get_language_options() {

	$languages = tp_twitch_get_languages();

	$options = array();

	if ( is_array( $languages ) && sizeof ( $languages ) > 0 ) {

		asort($languages);

		$options = array(
			'' => __( 'Please select...', 'tp-twitch-widget' )
		);

		$options = array_merge( $options, $languages );
	}

	return $options;
}

/**
 * Get widget size options
 *
 * @return array
 */
function tp_twitch_get_widget_size_options() {

	return array(
		'' => __( 'Please select...', 'tp-twitch-widget' ),
		'large' => __( 'Large', 'tp-twitch-widget' ),
		'small' => __( 'Small', 'tp-twitch-widget' ),
		'large-first' => __( 'First Large, Others Small', 'tp-twitch-widget' ),
	);
}

/**
 * Get widget preview options
 *
 * @return array
 */
function tp_twitch_get_widget_preview_options() {

	return array(
		'' => __( 'Please select...', 'tp-twitch-widget' ),
		'image' => __( 'Image', 'tp-twitch-widget' ),
		'video' => __( 'Video', 'tp-twitch-widget' ),
		'video-first' => __( 'First Video, Others Images', 'tp-twitch-widget' ),
	);
}

/**
 * Get streams key based on arguments
 *
 * @param array $args
 *
 * @return string
 */
function tp_twitch_get_streams_key( $args = array() ) {
	return 'tp_twitch_streams_' . md5( json_encode( $args ) );
}

/**
 * Get streams cache
 *
 * @param $args
 *
 * @return mixed
 */
function tp_twitch_get_streams_cache( $args ) {

	$streams_key = tp_twitch_get_streams_key( $args );

	$streams = get_transient( $streams_key );

	return $streams;
}

/**
 * Set streams cache
 *
 * @param $streams
 * @param $args
 */
function tp_twitch_set_streams_cache( $streams, $args ) {

	$options = tp_twitch_get_options();

	$cache_duration = ( ! empty( $options['cache_duration'] ) && is_numeric( $options['cache_duration'] ) ) ? $options['cache_duration'] : tp_twitch_get_option_default_value( 'cache_duration' );

	//tp_twitch_debug_log( 'tp_twitch_set_streams_cache >> $cache_duration: ' . $cache_duration );

	// Generate streams key
	$streams_key = tp_twitch_get_streams_key( $args );

	// Cache data
	set_transient( $streams_key, $streams, $cache_duration * HOUR_IN_SECONDS );
}

/**
 * Get streams
 *
 * @param array $args
 *
 * @return null
 */
function tp_twitch_get_streams( $args = array() ) {

	$streams = tp_twitch_get_streams_cache( $args );

	if ( ! empty( $streams ) )
		return tp_twitch_setup_streams( $streams );

	//tp_twitch_debug( 'tp_twitch_get_streams >> no cache!' );

	// First: Fetch streams from API
	$streams = tp_twitch_get_streams_from_api( $args );

	// Second: Validate data, fetch additional info from API and setup the final data structure
	$streams = tp_twitch_setup_streams_data( $streams );

	if ( ! empty( $streams ) )
		tp_twitch_set_streams_cache( $streams, $args );

	return tp_twitch_setup_streams( $streams );
}

/**
 * Setup streams
 *
 * @param $streams
 *
 * @return array
 */
function tp_twitch_setup_streams( $streams ) {

	if ( ! is_array( $streams ) )
		return $streams;

	// Build objects
	$streams_objects = array();

	if ( sizeof( $streams ) > 0 ) {

		foreach ( $streams as $stream ) {
			$streams_objects[] = ( is_array( $stream ) ) ? new TP_Twitch_Stream( $stream ) : $stream;
		}
	}

	return $streams_objects;
}

/**
 * Setup streams and maybe fetch additional data from API
 *
 * @param $streams
 *
 * @return array
 */
function tp_twitch_setup_streams_data( $streams ) {

	if ( ! is_array( $streams ) )
		return null;

	// Collect users
	$user_ids = array();

	foreach ( $streams as $stream ) {

		if ( ! empty( $stream['user_id'] ) ) {
			$user_ids[] = $stream['user_id'];
		}
	}

	$users = ( sizeof( $user_ids ) > 0 ) ? tp_twitch_get_users_from_api( array( 'user_id' => $user_ids ) ) : array();

	// Prepare users data
	$users_data = array();

	if ( is_array( $users ) && sizeof( $users ) > 0 ) {

		foreach ( $users as $user ) {

			/* Exemplary data.
			[id] => 19571641
            [login] => ninja
			[display_name] => Ninja
			[type] =>
            [broadcaster_type] => partner
			[description] => Professional Battle Royale player. Follow my twitter @Ninja and for more content subscribe to my Youtube.com/Ninja
			[profile_image_url] => https://static-cdn.jtvnw.net/jtv_user_pictures/6d942669-203f-464d-8623-db376ff971e0-profile_image-300x300.png
            [offline_image_url] => https://static-cdn.jtvnw.net/jtv_user_pictures/ninja-channel_offline_image-bb607ec9e64184fa-1920x1080.png
            [view_count] => 235274410
			*/

			$user_data = array(
				'id' => ( isset( $user['id'] ) ) ? $user['id'] : 0,
				'login' => ( isset( $user['login'] ) ) ? $user['login'] : '',
				'display_name' => ( isset( $user['display_name'] ) ) ? $user['display_name'] : '',
				'type' => ( isset( $user['type'] ) ) ? $user['type'] : '',
				'broadcaster_type' => ( isset( $user['broadcaster_type'] ) ) ? $user['broadcaster_type'] : '',
				'description' => ( isset( $user['description'] ) ) ? $user['description'] : '',
				'profile_image_url' => ( isset( $user['profile_image_url'] ) ) ? $user['profile_image_url'] : '',
				'offline_image_url' => ( isset( $user['offline_image_url'] ) ) ? $user['offline_image_url'] : '',
				'view_count' => ( isset( $user['view_count'] ) ) ? $user['view_count'] : 0,
			);

			$users_data[$user_data['id']] = $user_data;
		}
	}

	// Prepare streams data
	$streams_data = array();

	foreach ( $streams as $stream ) {

		/* Exemplary data.
		[id] => 29293315680
		[user_id] => 36769016
		[game_id] => 33214
		[community_ids] => Array
		(
			[0] => 2caef3bd-b3db-4eed-a748-f3ee124b33aa
		)

		[type] => live
		[title] => rocket launches today?! POGGERS
		[viewer_count] => 26415
		[started_at] => 2018-06-30T12:05:39Z
		[language] => en
		[thumbnail_url] => https://static-cdn.jtvnw.net/previews-ttv/live_user_timthetatman-{width}x{height}.jpg
		*/

		$stream_data = array(
			'id' => ( isset( $stream['id'] ) ) ? $stream['id'] : 0,
			'game_id' => ( isset( $stream['game_id'] ) ) ? $stream['game_id'] : 0,
			'community_ids' => ( isset( $stream['community_ids'] ) ) ? $stream['community_ids'] : '',
			'type' => ( isset( $stream['type'] ) ) ? $stream['type'] : '',
			'title' => ( isset( $stream['title'] ) ) ? $stream['title'] : '',
			'viewer_count' => ( isset( $stream['viewer_count'] ) ) ? $stream['viewer_count'] : 0,
			'started_at' => ( isset( $stream['started_at'] ) ) ? $stream['started_at'] : '',
			'language' => ( isset( $stream['language'] ) ) ? $stream['language'] : '',
			'thumbnail_url' => ( isset( $stream['thumbnail_url'] ) ) ? $stream['thumbnail_url'] : '',
			'user' => ( isset( $stream['user_id'] ) && isset( $users_data[$stream['user_id']] ) ) ? $users_data[$stream['user_id']] : null,
		);

		$streams_data[$stream_data['id']] = $stream_data;
	}

	//tp_twitch_debug( $streams_data, 'tp_twitch_setup_streams_data() >> $streams_data' );

	return $streams_data;
}