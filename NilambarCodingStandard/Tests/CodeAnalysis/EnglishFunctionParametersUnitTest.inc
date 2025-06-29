<?php
function fayzak_qc_register_stats_page() {
	add_menu_page(
			'こんにちは世界',
			'Hello World',
			'manage_options',
			'hello-stats',
			'hello_stats_page',
			'dashicons-chart-bar',
			1
	);
}

add_settings_field('text_color', '你好世界', 'setting_cb', 'my_settings', 'my_settings_section' );
add_settings_field('text_color', $custom_var, 'setting_cb', 'my_settings', 'my_settings_section' );
