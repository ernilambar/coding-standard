<?php
register_setting( 'my_options_group', 'my_option_name' ); // Bad.
register_setting( 'my_options_group', 'my_option_name', 10 ); // Bad.
register_setting( 'my_options_group', 'my_option_name', false ); // Bad.
register_setting( 'my_options_group', 'my_option_name', 'absint' ); // Good.
register_setting('my_options_group','my_option_name', 'sanitize_text_field' ); // Good.
register_setting('my_options_group', 'my_option_name', [ 'sanitize_callback' => 'sanitize_text_field']); // Good.
$args = array( 'sanitize_callback' => 'absint' );
register_setting( 'my_options_group', 'my_option_name', $args ); // Good.
