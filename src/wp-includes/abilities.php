<?php
/**
 * Core Abilities registration.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 6.9.0
 */

declare( strict_types = 1 );

/**
 * Registers the core ability categories.
 *
 * @since 6.9.0
 */
function wp_register_core_ability_categories(): void {
	wp_register_ability_category(
		'site',
		array(
			'label'       => __( 'Site' ),
			'description' => __( 'Abilities that retrieve or modify site information and settings.' ),
		)
	);

	wp_register_ability_category(
		'user',
		array(
			'label'       => __( 'User' ),
			'description' => __( 'Abilities that retrieve or modify user information and settings.' ),
		)
	);

	wp_register_ability_category(
		'navigation',
		array(
			'label'       => __( 'Navigation' ),
			'description' => __( 'Abilities that retrieve or modify navigation menus and their items.' ),
		)
	);
}

/**
 * Registers the default core abilities.
 *
 * @since 6.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function wp_register_core_abilities(): void {
	$category_site = 'site';
	$category_user = 'user';

	$site_info_properties = array(
		'name'        => array(
			'type'        => 'string',
			'title'       => __( 'Site Title' ),
			'description' => __( 'The site title.' ),
		),
		'description' => array(
			'type'        => 'string',
			'title'       => __( 'Tagline' ),
			'description' => __( 'The site tagline.' ),
		),
		'url'         => array(
			'type'        => 'string',
			'title'       => __( 'Site Address (URL)' ),
			'description' => __( 'The public URL where visitors access the site. May differ from the WordPress installation URL.' ),
		),
		'wpurl'       => array(
			'type'        => 'string',
			'title'       => __( 'WordPress Address (URL)' ),
			'description' => __( 'The URL where WordPress core files are served. May differ from the public site URL.' ),
		),
		'admin_email' => array(
			'type'        => 'string',
			'title'       => __( 'Administration Email Address' ),
			'description' => __( 'The site administrator email address.' ),
		),
		'charset'     => array(
			'type'        => 'string',
			'title'       => __( 'Site Charset' ),
			'description' => __( 'The site character encoding.' ),
		),
		'language'    => array(
			'type'        => 'string',
			'title'       => __( 'Site Language' ),
			'description' => __( 'The site locale in dash form (e.g. en-US).' ),
		),
		'version'     => array(
			'type'        => 'string',
			'title'       => __( 'WordPress Version' ),
			'description' => __( 'The WordPress core version running on this site.' ),
		),
	);
	$site_info_fields     = array_keys( $site_info_properties );

	wp_register_ability(
		'core/get-site-info',
		array(
			'label'               => __( 'Get Site Information' ),
			'description'         => __( 'Returns site information configured in WordPress. By default returns all fields, or optionally a filtered subset.' ),
			'category'            => $category_site,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'fields' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => $site_info_fields,
						),
						'description' => __( 'Optional: Limit response to specific fields. If omitted, all fields are returned.' ),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => $site_info_properties,
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) use ( $site_info_fields ): array {
				$input = is_array( $input ) ? $input : array();
				$requested_fields = ! empty( $input['fields'] ) ? $input['fields'] : $site_info_fields;

				$result = array();
				foreach ( $requested_fields as $field ) {
					if ( 'language' === $field ) {
						$result[ $field ] = str_replace( '_', '-', get_locale() );
					} else {
						$result[ $field ] = get_bloginfo( $field );
					}
				}

				return $result;
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	$user_info_properties = array(
		'id'            => array(
			'type'        => 'integer',
			'title'       => __( 'User ID' ),
			'description' => __( 'Unique identifier for the user.' ),
		),
		'display_name'  => array(
			'type'        => 'string',
			'title'       => __( 'Display Name' ),
			'description' => __( 'Public-facing name selected by the user.' ),
		),
		'user_nicename' => array(
			'type'        => 'string',
			'title'       => __( 'User Nicename' ),
			'description' => __( 'URL-friendly slug for the user. Defaults to the username.' ),
		),
		'user_login'    => array(
			'type'        => 'string',
			'title'       => __( 'Username' ),
			'description' => __( 'Login identifier for the user. Cannot be changed once set.' ),
		),
		'roles'         => array(
			'type'        => 'array',
			'title'       => __( 'Roles' ),
			'description' => __( 'Roles assigned to the user, such as administrator, editor, author, contributor, or subscriber.' ),
			'items'       => array(
				'type' => 'string',
			),
		),
		'locale'        => array(
			'type'        => 'string',
			'title'       => __( 'Language' ),
			'description' => __( 'Locale code for the user, such as en_US.' ),
		),
		'first_name'    => array(
			'type'        => 'string',
			'title'       => __( 'First Name' ),
			'description' => __( 'Given name.' ),
		),
		'last_name'     => array(
			'type'        => 'string',
			'title'       => __( 'Last Name' ),
			'description' => __( 'Family name.' ),
		),
		'nickname'      => array(
			'type'        => 'string',
			'title'       => __( 'Nickname' ),
			'description' => __( 'Informal name. Defaults to the username.' ),
		),
		'description'   => array(
			'type'        => 'string',
			'title'       => __( 'Biographical Info' ),
			'description' => __( 'User-authored biography. May be empty.' ),
		),
		'user_url'      => array(
			'type'        => 'string',
			'title'       => __( 'Website' ),
			'description' => __( 'Personal website URL.' ),
		),
	);
	$user_info_fields     = array_keys( $user_info_properties );

	wp_register_ability(
		'core/get-user-info',
		array(
			'label'               => __( 'Get User Information' ),
			'description'         => __( 'Returns profile details for the current authenticated user to support personalization, auditing, and access-aware behavior. By default returns all fields, or optionally a filtered subset.' ),
			'category'            => $category_user,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'fields' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => $user_info_fields,
						),
						'description' => __( 'Optional: Limit response to specific fields. If omitted, all fields are returned.' ),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => $user_info_properties,
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) use ( $user_info_fields ): array {
				$input            = is_array( $input ) ? $input : array();
				$requested_fields = ! empty( $input['fields'] ) ? $input['fields'] : $user_info_fields;
				$current_user     = wp_get_current_user();

				$all = array(
					'id'            => $current_user->ID,
					'display_name'  => $current_user->display_name,
					'user_nicename' => $current_user->user_nicename,
					'user_login'    => $current_user->user_login,
					// Ensure roles are encoded as a JSON array, regardless of their array keys.
					'roles'         => array_values( $current_user->roles ),
					'locale'        => get_user_locale( $current_user ),
					'first_name'    => $current_user->first_name,
					'last_name'     => $current_user->last_name,
					'nickname'      => $current_user->nickname,
					'description'   => $current_user->description,
					'user_url'      => $current_user->user_url,
				);

				return array_intersect_key( $all, array_flip( $requested_fields ) );
			},
			'permission_callback' => static function (): bool {
				return is_user_logged_in();
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	$environment_info_properties = array(
		'environment'    => array(
			'type'        => 'string',
			'title'       => __( 'Environment Type' ),
			'description' => __( 'The site\'s runtime environment classification.' ),
			'enum'        => array( 'production', 'staging', 'development', 'local' ),
		),
		'php_version'    => array(
			'type'        => 'string',
			'title'       => __( 'PHP Version' ),
			'description' => __( 'The PHP runtime version executing WordPress.' ),
		),
		'db_server_info' => array(
			'type'        => 'string',
			'title'       => __( 'Database Server Info' ),
			'description' => __( 'The database server vendor and version string reported by the driver.' ),
		),
		'wp_version'     => array(
			'type'        => 'string',
			'title'       => __( 'WordPress Version' ),
			'description' => __( 'The WordPress core version running on this site.' ),
		),
	);
	$environment_info_fields     = array_keys( $environment_info_properties );

	wp_register_ability(
		'core/get-environment-info',
		array(
			'label'               => __( 'Get Environment Info' ),
			'description'         => __( 'Returns core details about the site\'s runtime context for diagnostics and compatibility (environment, PHP runtime, database server info, WordPress version). By default returns all fields, or optionally a filtered subset.' ),
			'category'            => $category_site,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'fields' => array(
						'type'        => 'array',
						'items'       => array(
							'type' => 'string',
							'enum' => $environment_info_fields,
						),
						'description' => __( 'Optional: Limit response to specific fields. If omitted, all fields are returned.' ),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => $environment_info_properties,
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) use ( $environment_info_fields ): array {
				global $wpdb;

				/** @var array{ fields?: string[] } $input */
				$input            = is_array( $input ) ? $input : array();
				$requested_fields = ! empty( $input['fields'] ) ? $input['fields'] : $environment_info_fields;

				$db_server_info = '';
				if ( method_exists( $wpdb, 'db_server_info' ) ) {
					$db_server_info = $wpdb->db_server_info() ?? '';
				}

				$all = array(
					'environment'    => wp_get_environment_type(),
					'php_version'    => phpversion(),
					'db_server_info' => $db_server_info,
					'wp_version'     => get_bloginfo( 'version' ),
				);

				return array_intersect_key( $all, array_flip( $requested_fields ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'manage_options' );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	$category_navigation = 'navigation';

	wp_register_ability(
		'core/list-nav-menus',
		array(
			'label'               => __( 'List Navigation Menus' ),
			'description'         => __( 'Returns the registered navigation menu locations, which menu (if any) is assigned to each, and the list of all navigation menus that exist on the site.' ),
			'category'            => $category_navigation,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'locations' => array(
						'type'        => 'array',
						'title'       => __( 'Registered Locations' ),
						'description' => __( 'Theme-registered navigation menu locations and the menu currently assigned to each, if any.' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'location'      => array(
									'type'        => 'string',
									'description' => __( 'The location identifier registered by the theme.' ),
								),
								'description'   => array(
									'type'        => 'string',
									'description' => __( 'The human-readable label for this location.' ),
								),
								'assigned_menu' => array(
									'type'        => array( 'integer', 'null' ),
									'description' => __( 'The term ID of the menu assigned to this location, or null if none is assigned.' ),
								),
							),
						),
					),
					'menus'     => array(
						'type'        => 'array',
						'title'       => __( 'Navigation Menus' ),
						'description' => __( 'All navigation menus that exist on the site, regardless of whether they are assigned to a location.' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'    => array(
									'type'        => 'integer',
									'description' => __( 'The term ID of the menu.' ),
								),
								'name'  => array(
									'type'        => 'string',
									'description' => __( 'The display name of the menu.' ),
								),
								'slug'  => array(
									'type'        => 'string',
									'description' => __( 'The URL-friendly slug of the menu.' ),
								),
								'count' => array(
									'type'        => 'integer',
									'description' => __( 'The number of items in the menu.' ),
								),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$locations   = get_registered_nav_menus();
				$assignments = get_nav_menu_locations();

				$location_results = array();
				foreach ( $locations as $location => $description ) {
					$assigned_menu_id    = isset( $assignments[ $location ] ) ? (int) $assignments[ $location ] : 0;
					$location_results[] = array(
						'location'      => $location,
						'description'   => $description,
						'assigned_menu' => $assigned_menu_id ? $assigned_menu_id : null,
					);
				}

				$menu_results = array();
				foreach ( wp_get_nav_menus() as $menu ) {
					$menu_results[] = array(
						'id'    => $menu->term_id,
						'name'  => $menu->name,
						'slug'  => $menu->slug,
						'count' => (int) $menu->count,
					);
				}

				return array(
					'locations' => $location_results,
					'menus'     => $menu_results,
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_theme_options' );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	wp_register_ability(
		'core/get-nav-menu',
		array(
			'label'               => __( 'Get Navigation Menu' ),
			'description'         => __( 'Returns the ordered list of items in a navigation menu, given its ID or slug.' ),
			'category'            => $category_navigation,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'menu' => array(
						'type'        => array( 'integer', 'string' ),
						'description' => __( 'The menu term ID or slug to retrieve items for.' ),
					),
				),
				'required'             => array( 'menu' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'    => array(
						'type'        => 'integer',
						'description' => __( 'The term ID of the menu.' ),
					),
					'name'  => array(
						'type'        => 'string',
						'description' => __( 'The display name of the menu.' ),
					),
					'items' => array(
						'type'        => 'array',
						'description' => __( 'The ordered list of items in the menu.' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'     => array(
									'type'        => 'integer',
									'description' => __( 'The post ID of the menu item.' ),
								),
								'title'  => array(
									'type'        => 'string',
									'description' => __( 'The link text of the menu item.' ),
								),
								'url'    => array(
									'type'        => 'string',
									'description' => __( 'The URL the menu item links to.' ),
								),
								'parent' => array(
									'type'        => 'integer',
									'description' => __( 'The post ID of the parent menu item, or 0 for a top-level item.' ),
								),
								'order'  => array(
									'type'        => 'integer',
									'description' => __( 'The position of the item within its parent.' ),
								),
								'target' => array(
									'type'        => 'string',
									'description' => __( 'The link target attribute, such as _blank. Empty if unset.' ),
								),
							),
						),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) {
				$input      = is_array( $input ) ? $input : array();
				$menu_input = $input['menu'] ?? '';
				$menu       = wp_get_nav_menu_object( $menu_input );

				if ( ! $menu ) {
					return new WP_Error( 'ability_invalid_input', __( 'No navigation menu exists with that ID or slug.' ) );
				}

				$items = wp_get_nav_menu_items( $menu->term_id );
				if ( ! $items ) {
					$items = array();
				}
				$item_results = array();

				foreach ( $items as $item ) {
					$item_results[] = array(
						'id'     => (int) $item->ID,
						'title'  => $item->title,
						'url'    => $item->url,
						'parent' => (int) $item->menu_item_parent,
						'order'  => (int) $item->menu_order,
						'target' => $item->target,
					);
				}

				return array(
					'id'    => $menu->term_id,
					'name'  => $menu->name,
					'items' => $item_results,
				);
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_theme_options' );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);
}
