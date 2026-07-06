<?php

declare( strict_types=1 );

/**
 * Tests for the core navigation-menu abilities shipped with the Abilities API.
 *
 * @covers wp_register_core_ability_categories
 * @covers wp_register_core_abilities
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpRegisterNavMenuAbilities extends WP_UnitTestCase {

	/**
	 * Set up before the class.
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// Ensure core abilities are registered for these tests.
		// Temporarily remove the unhook functions so we can register core abilities.
		remove_action( 'wp_abilities_api_categories_init', '_unhook_core_ability_categories_registration', 1 );
		remove_action( 'wp_abilities_api_init', '_unhook_core_abilities_registration', 1 );

		// Add the core registration hooks and fire the actions.
		add_action( 'wp_abilities_api_categories_init', 'wp_register_core_ability_categories' );
		add_action( 'wp_abilities_api_init', 'wp_register_core_abilities' );
		do_action( 'wp_abilities_api_categories_init' );
		do_action( 'wp_abilities_api_init' );
	}

	/**
	 * Tear down after the class.
	 */
	public static function tear_down_after_class(): void {
		// Re-add the unhook functions for subsequent tests.
		add_action( 'wp_abilities_api_categories_init', '_unhook_core_ability_categories_registration', 1 );
		add_action( 'wp_abilities_api_init', '_unhook_core_abilities_registration', 1 );

		// Remove the core abilities and their categories.
		foreach ( wp_get_abilities() as $ability ) {
			wp_unregister_ability( $ability->get_name() );
		}
		foreach ( wp_get_ability_categories() as $ability_category ) {
			wp_unregister_ability_category( $ability_category->get_slug() );
		}

		parent::tear_down_after_class();
	}

	/**
	 * Tests that the `core/list-nav-menus` ability is registered with the expected schema.
	 */
	public function test_core_list_nav_menus_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/list-nav-menus' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );
		$this->assertSame( 'navigation', $ability->get_category() );

		$output_schema = $ability->get_output_schema();

		$this->assertArrayHasKey( 'locations', $output_schema['properties'] );
		$this->assertArrayHasKey( 'menus', $output_schema['properties'] );
	}

	/**
	 * Tests that `core/list-nav-menus` requires the edit_theme_options capability.
	 */
	public function test_core_list_nav_menus_requires_capability(): void {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$ability = wp_get_ability( 'core/list-nav-menus' );
		$this->assertFalse( $ability->check_permissions() );

		$result = $ability->execute();
		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Tests that `core/list-nav-menus` returns registered locations, assignments, and menus.
	 */
	public function test_core_list_nav_menus_executes(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		register_nav_menu( 'test-primary', 'Test Primary' );

		$menu_id = wp_create_nav_menu( 'Test Menu' );
		set_theme_mod(
			'nav_menu_locations',
			array( 'test-primary' => $menu_id )
		);

		$ability = wp_get_ability( 'core/list-nav-menus' );
		$result  = $ability->execute();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'locations', $result );
		$this->assertArrayHasKey( 'menus', $result );

		$location_slugs = wp_list_pluck( $result['locations'], 'location' );
		$this->assertContains( 'test-primary', $location_slugs );

		$location_index = array_search( 'test-primary', $location_slugs, true );
		$this->assertSame( $menu_id, $result['locations'][ $location_index ]['assigned_menu'] );

		$menu_ids = wp_list_pluck( $result['menus'], 'id' );
		$this->assertContains( $menu_id, $menu_ids );
	}

	/**
	 * Tests that the `core/get-nav-menu` ability is registered with the expected schema.
	 */
	public function test_core_get_nav_menu_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/get-nav-menu' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );
		$this->assertSame( 'navigation', $ability->get_category() );

		$input_schema = $ability->get_input_schema();
		$this->assertContains( 'menu', $input_schema['required'] );
	}

	/**
	 * Tests that `core/get-nav-menu` returns the ordered items of a menu.
	 */
	public function test_core_get_nav_menu_executes(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$menu_id = wp_create_nav_menu( 'Items Menu' );

		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Home',
				'menu-item-url'    => home_url( '/' ),
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);

		$ability = wp_get_ability( 'core/get-nav-menu' );
		$result  = $ability->execute( array( 'menu' => $menu_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( $menu_id, $result['id'] );
		$this->assertSame( 'Items Menu', $result['name'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( $item_id, $result['items'][0]['id'] );
		$this->assertSame( 'Home', $result['items'][0]['title'] );
		$this->assertSame( home_url( '/' ), $result['items'][0]['url'] );
	}

	/**
	 * Tests that `core/get-nav-menu` returns a WP_Error for an unknown menu.
	 */
	public function test_core_get_nav_menu_rejects_unknown_menu(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$ability = wp_get_ability( 'core/get-nav-menu' );
		$result  = $ability->execute( array( 'menu' => 'not-a-real-menu-slug' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * Tests that `core/get-nav-menu` requires the edit_theme_options capability.
	 */
	public function test_core_get_nav_menu_requires_capability(): void {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$menu_id = wp_create_nav_menu( 'Gated Menu' );

		$ability = wp_get_ability( 'core/get-nav-menu' );
		$this->assertFalse( $ability->check_permissions() );

		$result = $ability->execute( array( 'menu' => $menu_id ) );
		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}
}
