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

			// Donations columns
			add_filter( 'manage_edit-donation_columns', array( $this, 'dashboard_columns' ), 11, 1 );
			add_filter( 'manage_donation_posts_custom_column', array( $this, 'dashboard_column_item' ), 11, 2 );
			add_filter( 'manage_edit-donation_sortable_columns', array( $this, 'sortable_columns' ) );
			add_filter( 'list_table_primary_column', array( $this, 'primary_column' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'row_actions' ), 2, 100 );

			// Bulk edit
			add_filter( 'bulk_actions-edit-donation', array( $this, 'bulk_actions' ) );

			// Customization filters
			add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 10, 2 );
			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			add_action( 'manage_posts_extra_tablenav', array( $this, 'extra_tablenav' ) );

			// Export
			add_action( 'admin_footer', array( $this, 'export' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'setup_scripts' ) );

			// Sorting query
			add_filter( 'request', array( $this, 'request_query' ) );
			add_filter( 'posts_clauses', array( $this, 'posts_clauses' ) );

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
		 * @deprecated   1.4.0
		 */
		public function handle_donation_status_change( $new_status, $old_status, $post ) {

			_deprecated_function( __METHOD__, '1.4.0', 'Handled automatically when $donation->update_status() is called.' );

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

			charitable_get_donation( $post->ID )->update_donation_log( $message );
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
				'cb'            => '<input type="checkbox"/>',
				'id'            => __( 'Donation', 'charitable' ),
				'amount'        => __( 'Amount Donated', 'charitable' ),
				'campaigns'     => __( 'Campaign(s)', 'charitable' ),
				'donation_date' => __( 'Date', 'charitable' ),
				'post_status'   => __( 'Status', 'charitable' ),
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

			$donation = charitable_get_donation( $post_id );

			switch ( $column_name ) {

				case 'id' :

					$title 	 = esc_attr__( 'View Donation Details', 'charitable' );
					$name 	 = $donation->get_donor()->get_name();

					if ( $name ) {
						$text = sprintf( _x( '#%d by %s', 'number symbol', 'charitable' ),
							$donation->get_donation_id(),
							$name
						);
					} else {
						$text = sprintf( _x( '#%d', 'number symbol', 'charitable' ),
							$donation->get_donation_id()
						);
					}

					$url 	 = esc_url( add_query_arg( array(
						'post'   => $donation->get_donation_id(),
						'action' => 'edit',
					), admin_url( 'post.php' ) ) );

					$display = sprintf( '<a href="%s" title="%s">%s</a>', $url, $title, $text );

					break;

				case 'post_status' :

					$display = sprintf( '<mark class="status %s">%s</mark>',
						esc_attr( $donation->get_status() ),
						strtolower( $donation->get_status( true ) )
					);

					break;

				case 'amount' :

					$display = charitable_format_money( $donation->get_total_donation_amount() );
					$display .= '<span class="meta">' . sprintf( _x( 'via %s', 'charitable' ), $donation->get_gateway_label() ). '</span>';

					break;

				case 'campaigns' :

	                $campaigns = array();

	                foreach ( $donation->get_campaign_donations() as $cd ) {

	                    $campaigns[] = sprintf( '<a href="edit.php?post_type=%s&campaign_id=%s">%s</a>',
	                        Charitable::DONATION_POST_TYPE,
	                        $cd->campaign_id,
	                        $cd->campaign_name
	                    );
	                }

	                $display = implode( ', ', $campaigns );

					break;

				case 'donation_date' :

					$display = $donation->get_date();

					break;

				default :

					$display = '';

					break;

			}

			echo apply_filters( 'charitable_donation_column_display', $display, $column_name, $post_id, $donation );
		}

		/**
		 * Make columns sortable.
		 *
		 * @param   array $columns  .
		 * @return  array
		 * @access  public
		 * @since   1.4.0
		 */
		public function sortable_columns( $columns ) {
			$sortable_columns = array(
				'id'            => 'ID',
				'amount'        => 'amount',
				'donation_date' => 'date',
				'post_status'   => 'post_status',
			);

			return wp_parse_args( $sortable_columns, $columns );
		}

	    /**
		 * Set list table primary column for donations.
		 *
		 * Support for WordPress 4.3.
		 *
		 * @param  string $default
		 * @param  string $screen_id
		 * @return string
		 * @since  1.4.0
		 */
		public function primary_column( $default, $screen_id ) {
			if ( 'edit-donation' === $screen_id ) {
				return 'id';
			}

			return $default;
		}

		/**
		 * Set row actions for donations.
		 *
		 * @param  array   $actions
		 * @param  WP_Post $post
		 * @return array
		 * @since  1.4.0
		 */
		public function row_actions( $actions, $post ) {
			if ( Charitable::DONATION_POST_TYPE !== $post->post_type ) {
				return $actions;
			}

			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			if ( isset( $actions['edit'] ) ) {

				$title 	= esc_attr__( 'View Details', 'charitable' );
				$text   = __( 'View', 'charitable' );
				$url    = esc_url( add_query_arg( array(
					'post' => $post->ID,
					'action' => 'edit',
				), admin_url( 'post.php' ) ) );

				$actions['edit'] = sprintf( '<a href="%s" title="%s">%s</a>', $url, $title, $text );

			}

			return $actions;
		}

		/**
		 * Remove edit from the bulk actions.
		 *
		 * @param  array $actions
		 * @return array
		 * @since  1.4.0
		 */
		public function bulk_actions( $actions ) {
			return array();
		}

		/**
	     * Retrieve the bulk actions
	     *
	     * @access public
	     * @since 1.0.0
	     * @return array $actions Array of the bulk actions
	     */
	    public function get_bulk_actions() {
	        $actions = array();
	        
	        foreach ( charitable_get_valid_donation_statuses() as $status_key => $label ) {
	            $actions[ 'set-' . $status_key ] = sprintf( _x( 'Set to %s', 'set donation status to x', 'charitable' ), $label );
	        }
	        $actions[ 'delete' ] = __( 'Delete', 'charitable' );
	        return apply_filters( 'charitable_donations_table_bulk_actions', $actions );
	    }


		/**
		 * Disable the month's dropdown (will replace with custom range search).
		 *
		 * @param mixed $public_query_vars
		 * @param  str $post_type
		 * @return array
		 * @since  1.4.0
		 */
		public function disable_months_dropdown( $disable, $post_type ) {
			if ( Charitable::DONATION_POST_TYPE == $post_type ) {
				$disable = true;
			}

			return $disable;
		}

		/**
		 * Add date-based filters above the donations table.
		 *
		 * @param  string $post_type
		 * @since  1.4.0
		 */
		public function restrict_manage_posts( $post_type = '' ) {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {

				// until WordPress core fixes: https://core.trac.wordpress.org/ticket/16031.
				charitable_admin_view( 'donations-page/bulk-actions', array( 'which' => 'top', 'actions' => $this->get_bulk_actions() ) );

				charitable_admin_view( 'donations-page/filters' );

			}
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @param   string $which
		 * @return 	void
		 * @access 	public
		 * @since   1.4.0
		 */
		public function extra_tablenav( $which ) {
			global $typenow;

			/* Add the export button. */
			if ( 'top' == $which && in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/export' );
			}

			/* restore the bottom bulk actions. */
			if ( 'bottom' == $which && in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/bulk-actions', array( 'which' => 'top', 'actions' => $this->get_bulk_actions() ) );
			}
		}


		/**
		 * Add export template to footer
		 *
		 * @param   string $which
		 * @return 	void
		 * @access 	public
		 * @since   1.4.0
		 */
		public function export() {
			global $typenow;

			/* Add the export form. */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/export-form' );
			}


		}
		

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @return 	void
		 * @access 	public
		 * @since  	1.4.0
		 */
		public function setup_scripts() {
			global $post_type;

			if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
				$suffix  = '';
				$version = time();
			} else {
				$suffix  = '.min';
				$version = charitable()->get_version();
			}

			$assets_path = charitable()->get_path( 'assets', false );

			wp_register_script( 'lean-modal',
				$assets_path . 'js/libraries/leanModal' . $suffix . '.js',
				array( 'jquery-core' ),
				$version
			);

			wp_register_style( 'lean-modal-css',
				$assets_path . 'css/modal' . $suffix . 'css',
				array(),
				$version
			);

			wp_register_script( 'charitable-admin-donations',
				$assets_path . 'js/charitable-admin-donations' . $suffix . '.js',
				array( 'jquery-core', 'lean-modal' ),
				$version
			);
		}

		/**
		 * Add custom filters to the query that returns the donations to be displayed.
		 *
		 * @param   array $vars
		 * @return  array
		 * @access 	public
		 * @since   1.4.0
		 */
		public function request_query( $vars ) {
			global $typenow;

			if ( Charitable::DONATION_POST_TYPE != $typenow ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $vars['post_status'] ) ) {
				$vars['post_status'] = array_keys( charitable_get_valid_donation_statuses() );
			}

			/* Set up date query */
			if ( isset( $_GET['start_date'] ) && ! empty( $_GET['start_date'] ) ) {

				$start_date = $this->get_parsed_date( $_GET['start_date'] );

				$vars['date_query']['after'] = array(
					'year'  => $start_date['year'],
					'month' => $start_date['month'],
					'day'   => $start_date['day'],
				);
			}

			if ( isset( $_GET['end_date'] ) && ! empty( $_GET['end_date'] ) ) {

				$end_date = $this->get_parsed_date( $_GET['end_date'] );

				$vars['date_query']['before'] = array(
					'year'  => $end_date['year'],
					'month' => $end_date['month'],
					'day'   => $end_date['day'],
				);
			}

			/* Filter by campaign. */
            if ( isset( $_GET[ 'campaign_id' ] ) && ! empty( $_GET[ 'campaign_id' ] ) ) {
               
                $ids = charitable_get_table( 'campaign_donations' )->get_donations_on_campaign( intval( $_GET[ 'campaign_id' ] ) );

                if ( ! empty( $ids ) ) {  
                    $ids = wp_list_pluck( $ids, 'donation_id' );
                    $vars[ 'post__in' ] = (array) $ids;
                }
            }

			return $vars;
		}

		/**
		 * Column sorting handler.
		 *
		 * @param   array $vars
		 * @return  array
		 * @access 	public
		 * @since   1.4.0
		 */
		public function posts_clauses( $clauses ) {
			global $typenow, $wpdb;

			if ( Charitable::DONATION_POST_TYPE != $typenow ) {
				return $clauses;
			}

			if ( ! isset( $vars['orderby'] ) ) {
				return $clauses;
			}

			/* Sorting */
			$order = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) == 'ASC' ? 'ASC' : 'DESC';

			switch ( $vars['orderby'] ) {

				case 'amount' :
					$clauses['join'] = "JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donation_id = $wpdb->posts.ID ";
					$clauses['orderby'] = 'cd.amount ' . $order;
					break;

				case 'status' :
					$clauses['orderby'] = $wpdb->posts . '.post_status ' . $order;
					break;

			}

			return $clauses;
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 *
		 * @return  string[]
		 * @access  protected
		 * @since   1.4.0
		 */
		protected function get_parsed_date( $date ) {
			$time = strtotime( $date );

			return array(
				'year'  => date( 'Y', $time ),
				'month' => date( 'm', $time ),
				'day'   => date( 'd', $time ),
			);
		}
	}

endif; // End class_exists check
