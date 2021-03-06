<?php
/**
 * The class that defines how donations are managed on the admin side.
 *
 * @package     Charitable/Classes/Charitable_Donation_Post_Type
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2015, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Charitable_Donation_Post_Type' ) ) :

	/**
	 * Charitable_Donation_Post_Type class.
	 *
	 * @final
	 * @since       1.0.0
	 */
	final class Charitable_Donation_Post_Type {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Donation_Post_Type|null
		 * @access  private
		 * @static
		 */
		private static $instance = null;

		/**
		 * @var     Charitable $charitable
		 * @access  private
		 */
		private $charitable;

		/**
		 * @var     Charitable_Meta_Box_Helper $meta_box_helper
		 * @access  private
		 */
		private $meta_box_helper;

		/**
		 * Create object instance.
		 *
		 * @access  private
		 * @since   1.0.0
		 */
		private function __construct() {
			$this->meta_box_helper = new Charitable_Meta_Box_Helper( 'charitable-donation' );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 20 );
			add_action( 'transition_post_status', array( $this, 'handle_donation_status_change' ), 10, 3 );

			// Add fields to the dashboard listing of donations.
			add_filter( 'manage_edit-donation_columns',         array( $this, 'dashboard_columns' ), 11, 1 );
			add_filter( 'manage_donation_posts_custom_column',  array( $this, 'dashboard_column_item' ), 11, 2 );
			add_filter( 'views_edit-donation',                  array( $this, 'view_options' ) );

			do_action( 'charitable_admin_donation_post_type_start', $this );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @return  Charitable_Donation_Post_Type
		 * @access  public
		 * @since   1.2.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Donation_Post_Type();
			}

			return self::$instance;
		}

		/**
		 * Sets up the meta boxes to display on the donation admin page.
		 *
		 * @return  void
		 * @access  public
		 * @since   1.0.0
		 */
		public function add_meta_boxes() {
			foreach ( $this->get_meta_boxes() as $meta_box_id => $meta_box ) {
				add_meta_box(
					$meta_box_id,
					$meta_box['title'],
					array( $this->meta_box_helper, 'metabox_display' ),
					Charitable::DONATION_POST_TYPE,
					$meta_box['context'],
					$meta_box['priority'],
					$meta_box
				);
			}
		}

		/**
		 * Remove default meta boxes.
		 *
		 * @return  void
		 * @access  public
		 * @since   1.0.0
		 */
		public function remove_meta_boxes() {
			global $wp_meta_boxes;

			$charitable_meta_boxes = $this->get_meta_boxes();

			foreach ( $wp_meta_boxes[ Charitable::DONATION_POST_TYPE ] as $context => $priorities ) {
				foreach ( $priorities as $priority => $meta_boxes ) {
					foreach ( $meta_boxes as $meta_box_id => $meta_box ) {
						if ( ! isset( $charitable_meta_boxes[ $meta_box_id ] ) ) {
							remove_meta_box( $meta_box_id, Charitable::DONATION_POST_TYPE, $context );
						}
					}
				}
			}
		}

		/**
		 * Respond to changes in donation status.
		 *
		 * @param   string $new_status
		 * @param   string $old_status
		 * @param   WP_Post $post
		 * @return  void
		 * @access  public
		 * @since   1.2.0
		 */
		public function handle_donation_status_change( $new_status, $old_status, $post ) {
			if ( Charitable::DONATION_POST_TYPE != $post->post_type ) {
				return;
			}

			$valid_statuses = charitable_get_valid_donation_statuses();

			if ( 'new' == $old_status ) {
				$message = sprintf( __( 'Donation status set to %s.', 'charitable' ),
					$valid_statuses[ $new_status ]
				);
			} else {
				$message = sprintf( __( 'Donation status updated from %s to %s.', 'charitable' ),
					$valid_statuses[ $old_status ],
					$valid_statuses[ $new_status ]
				);
			}

			charitable_update_donation_log( $post->ID, $message );
		}

		/**
		 * Returns an array of all meta boxes added to the donation post type screen.
		 *
		 * @return  array
		 * @access  private
		 * @since   1.0.0
		 */
		private function get_meta_boxes() {
			$meta_boxes = array(
				'donation-overview'  => array(
					'title'         => __( 'Donation Overview', 'charitable' ),
					'context'       => 'normal',
					'priority'      => 'high',
					'view'          => 'metaboxes/donation/donation-overview',
				),
				'donation-details'     => array(
					'title'         => __( 'Donation Details', 'charitable' ),
					'context'       => 'side',
					'priority'      => 'high',
					'view'          => 'metaboxes/donation/donation-details',
				),
				'donation-log'      => array(
					'title'         => __( 'Donation Log', 'charitable' ),
					'context'       => 'normal',
					'priority'      => 'low',
					'view'          => 'metaboxes/donation/donation-log',
				),
			);

			return apply_filters( 'charitable_donation_meta_boxes', $meta_boxes );
		}

		/**
		 * Customize donations columns.
		 *
		 * @see     get_column_headers
		 *
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function dashboard_columns( $column_names ) {
			$column_names = apply_filters( 'charitable_donation_dashboard_column_names', array(
				'cb'                => '<input type="checkbox"/>',
				'id'                => __( 'ID', 'charitable' ),
				'donor'             => __( 'Donor', 'charitable' ),
				'details'           => __( 'Details', 'charitable' ),
				'amount'            => __( 'Amount Donated', 'charitable' ),
				'campaigns'         => __( 'Campaign(s)', 'charitable' ),
				'donation_date'     => __( 'Date', 'charitable' ),
				'status'            => __( 'Status', 'charitable' ),
			) );

			return $column_names;
		}

		/**
		 * Add information to the dashboard donations table listing.
		 *
		 * @see     WP_Posts_List_Table::single_row()
		 *
		 * @param   string  $column_name    The name of the column to display.
		 * @param   int     $post_id        The current post ID.
		 * @return  void
		 * @access  public
		 * @since   1.0.0
		 */
		public function dashboard_column_item( $column_name, $post_id ) {

			$donation = $this->get_donation( $post_id );

			switch ( $column_name ) {
				case 'id' :
					$display = $donation->get_number();
					break;

				case 'donor' :
					$display = $donation->get_donor()->get_name();
					break;

				case 'details' :
					$display = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post' => $donation->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ) ),
					__( 'View Donation Details', 'charitable' ) );
					break;

				case 'amount' :
					$display = charitable_format_money( $donation->get_total_donation_amount() );
					break;

				case 'campaigns' :
					$display = implode( ', ', $donation->get_campaigns() );
					break;

				case 'donation_date' :
					$display = $donation->get_date();
					break;

				case 'status' :
					$display = $donation->get_status( true );
					break;

				default :
					$display = '';
					break;
			}

			echo apply_filters( 'charitable_donation_column_display', $display, $column_name, $post_id, $donation );
		}

		/**
		 * Returns the donation object. Caches the object to avoid re-creating this for each column.
		 *
		 * @return  Charitable_Donation
		 * @access  private
		 * @since   1.0.0
		 */
		private function get_donation( $post_id ) {
			$donation = wp_cache_get( $post_id, 'charitable_donation' );

			if ( false === $donation ) {

				$donation = charitable_get_donation( $post_id );

				wp_cache_set( $post_id, $donation, 'charitable_donation' );

			}

			return $donation;
		}

		/**
		 * Returns the array of view options for this campaign.
		 *
		 * @param   array       $views
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function view_options( $views ) {

			$current        = isset( $_GET['post-status'] ) ? $_GET['post-status'] : '';
			$statuses       = charitable_get_valid_donation_statuses();
			$donations      = new Charitable_Donations();
			$status_count   = $donations->count_by_status();

			$views          = array();
			$views['all']   = sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
				esc_url( remove_query_arg( array( 'post_status', 'paged' ) ) ),
				'all' === $current || '' == $current ? ' class="current"' : '',
				__( 'All', 'charitable' ),
				$donations->count_all()
			);

			foreach ( $statuses as $status => $label ) {
				$views[ $status ] = sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>',
					esc_url( add_query_arg( array( 'post_status' => $status, 'paged' => false ) ) ),
					$current === $status ? ' class="current"' : '',
					$label,
					isset( $status_count[ $status ] ) ? $status_count[ $status ]->num_donations : 0
				);
			}

			return $views;
		}
	}

endif; // End class_exists check
