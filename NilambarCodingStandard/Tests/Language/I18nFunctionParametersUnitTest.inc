<?php
function custom_stats_page() {
	add_menu_page( // Bad.
			'こんにちは世界',
			'Hello World',
			'manage_options',
			'hello-stats',
			'hello_stats_page',
			'dashicons-chart-bar',
			1
	);

	add_submenu_page( 'parent-slug', 'سلام', 'नमस्ते', 'manage_options', 'hello-stats', 'hello_stats_page', 'dashicons-chart-bar', 1 ); // // Bad x 2.
}

add_meta_box( 'my_meta_box', 'مرحبا بالعالم', 'meta_box_callback', 'post' ); // Bad.
add_meta_box( 'my_meta_box', 'Hello World', 'meta_box_callback', 'post' ); // Good.

add_settings_field('text_color', '你好世界', 'setting_cb', 'my_settings', 'my_settings_section' ); // Bad.
add_settings_field('text_color', $custom_var, 'setting_cb', 'my_settings', 'my_settings_section' ); // Good.

add_settings_section( 'my_section', 'Привет мир', 'section_callback', 'my_settings' ); // Bad.
add_settings_section( 'my_section', 'Hello World', 'section_callback', 'my_settings' ); // Good.

register_nav_menu( 'primary', 'Γεια σας κόσμος' ); // Bad.
register_nav_menu( 'primary', 'Primary Navigation Menu' ); // Good.

add_options_page( 'مرحبا بالعالم', 'Hello World', 'manage_options', 'my-options', 'options_callback' ); // Bad.
add_options_page( 'My Options Page', 'My Options', 'manage_options', 'my-options', 'options_callback' ); // Good.

wp_add_dashboard_widget( 'my_widget', 'こんにちは世界', 'widget_callback' ); // Bad.
wp_add_dashboard_widget( 'my_widget', 'My Dashboard Widget', 'widget_callback' ); // Good.

add_submenu_page(
	'custom-notification', // Slug du menu principal de ton plugin.
	'Quickstart',
	'Quickstart',
	'manage_options',
	'abs_quickstart',
	'abs_quickstart_admin_page'
);
