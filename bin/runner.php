<?php
function ncs_generate_vars_file( string $source_file_path, string $output_file_path, int $buffer_size = 1000 ) {
	$config_data = include $source_file_path;

	$file_handle = fopen( $output_file_path, 'w' );
	fwrite( $file_handle, "<?php\nreturn [\n" );

	$current_buffer = '';
	$keys_processed = 0;

	foreach ( array_keys( $config_data['messages'] ) as $key ) {
		$current_buffer .= '    ' . var_export( $key, true ) . ",\n";

		++$keys_processed;

		if ( $keys_processed % $buffer_size === 0 ) {
			fwrite( $file_handle, $current_buffer );
			$current_buffer = '';
		}
	}

	// Write remaining buffer.
	if ( ! empty( $current_buffer ) ) {
		fwrite( $file_handle, $current_buffer );
	}

	fwrite( $file_handle, "];\n" );
	fclose( $file_handle );
}

ncs_generate_vars_file( 'data/i18n/core.php', 'NilambarCodingStandard/Vars/core.php' );
ncs_generate_vars_file( 'data/i18n/admin.php', 'NilambarCodingStandard/Vars/admin.php' );
