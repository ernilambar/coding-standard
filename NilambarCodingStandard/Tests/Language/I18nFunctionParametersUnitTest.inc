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

add_settings_field('text_color', '你好世界', 'setting_cb', 'my_settings', 'my_settings_section' ); // Bad.
add_settings_field('text_color', $custom_var, 'setting_cb', 'my_settings', 'my_settings_section' ); // Good.
