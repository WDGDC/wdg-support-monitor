<?php

namespace WDGDC\SupportMonitor;

if ( class_exists( 'WP_CLI_Command' ) ) :
	/**
	 * Get information from the WDG Support Monitor Plugin
	 */
	class Command extends \WP_CLI_Command {

		/**
		 * Execute the support status and post to the configured endpoint
		 */
		public function update() {
			Monitor::get_instance()->post( true ); // Blocking request
		}

		/**
		 * Get the configured secret - either a wp-config constant or a hash of the server name
		 *
		 * ## OPTIONS
		 *
		 * [--format=<format>]
		 * : Render the data in the specified format
		 * ---
		 * default: table
		 * options:
		 *   - table
		 *   - csv
		 *   - json
		 *   - yaml
		 */
		public function info( $args, $assoc_args ) {
			$monitor = Monitor::get_instance();

			$data = [
				'API Endpoint' => $monitor->post_url,
				'API Secret'   => $monitor->secret_key,
				'Last Run'     => $monitor->get_last_run() ? $monitor->get_last_run()->timestamp : 'Never',
			];

			$next_scheduled = wp_next_scheduled( $monitor::EVENT );
			$data[ 'Next Scheduled' ] = ( $next_scheduled ) ? date( 'Y-m-d h:i:s', $next_scheduled ) : 'Not scheduled';

			\WP_CLI\Utils\format_items( $assoc_args['format'], [ $data ], array_keys( $data ) );
		}

		/**
		 * Output the report in a CLI friendly format
		 *
		 * ## OPTIONS
		 *
		 * [--format=<format>]
		 * : Render the data in the specified format
		 * ---
		 * default: yaml
		 * options:
		 *   - yaml
		 *   - json
		 *   - table
		 *
		 * [--pretty]
		 * : Pretty print the JSON report
		 * ---
		 * default: true
		 * options:
		 *   - true
		 *   - false
		 */
		public function report( $args, $assoc_args ) {
			$data = Monitor::get_instance()->compile();

			switch( $assoc_args['format'] ) {
				case 'json':
					\WP_CLI::line( json_encode( $data, ! empty( $assoc_args['pretty'] ) ? JSON_PRETTY_PRINT : 0 ) );
				break;
				case 'table':
					$tables = [];

					foreach( $data as $key => $row ) {
						if ( ! is_scalar( $row ) ) {
							if ( is_array( $row ) ) {
								$tables[ $key ] = $row;
							}

							if ( is_object( $row ) ) {
								foreach( $row as $prop => $val ) {
									$data->$prop = $val;
								}
							}

							unset( $data->$key );
						}
					}

					\WP_CLI\Utils\format_items( $assoc_args['format'], [ $data ], array_keys( (array) $data ) );

					foreach( $tables as $table ) {
						\WP_CLI\Utils\format_items( $assoc_args['format'], $table, array_keys( (array) current( $table ) ) );
					}
				break;
				default: // yaml
					\WP_CLI\Utils\format_items( $assoc_args['format'], [ $data ], array_keys( (array) current( $data ) ) );
				break;
			}
		}
	}

endif;