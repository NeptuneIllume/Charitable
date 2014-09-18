<?php 
/**
 * Charitable Donations DB class. 
 *
 * @package     Charitable
 * @subpackage  Classes/Charitable Donations DB
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Donations_DB' ) ) : 

/**
 * Charitable_Donations_DB
 *
 * @since 		0.1 
 */
class Charitable_Donations_DB extends Charitable_DB {

	/**
	 * Whitelist of columns
	 *
	 * @return  array 
	 * @access  public
	 * @since   0.1
	 */
	public function get_columns() {
		return array(
			'id'				=> '%d', 
			'campaign_id'		=> '%d',
			'user_id'			=> '%d',
			'date_created'		=> '%s',
			'amount'			=> '%f',
			'gateway'			=> '%s', 
			'is_preset_amount'	=> '%d', 
			'notes'				=> '%s'	
		);
	}

	/**
	 * Default column values
	 *
	 * @return 	array
	 * @access  public
	 * @since   0.1
	 */
	public function get_column_defaults() {
		return array(
			'id'				=> '', 
			'campaign_id'		=> '',
			'user_id'			=> 0,
			'date_created'		=> date('Y-m-d h:i:s'),
			'amount'			=> '',
			'gateway'			=> '', 
			'is_preset_amount'	=> 0, 
			'notes'				=> ''	
		);
	}

	/**
	 * Create the table.
	 *
	 * @global 	$wpdb
	 *
	 * @access 	public
	 * @since 	0.1
	 */
	public function create_table() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		`id` bigint(20) NOT NULL AUTO_INCREMENT,
		`campaign_id` bigint(20) NOT NULL,
		`user_id` bigint(20) NOT NULL,
		`date_created` datetime NOT NULL,
		`amount` float NOT NULL,
		'gateway' varchar(50) NOT NULL,
		'is_preset_amount' tinyint NOT NULL,
		`notes` longtext NOT NULL,
		PRIMARY KEY  (id),
		KEY user (user_id),
		KEY campaign (campaign_id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}

endif;