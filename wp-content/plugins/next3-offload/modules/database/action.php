<?php 
namespace Next3Offload\Modules\Database;
defined( 'ABSPATH' ) || exit;

class Action{
    
    private static $instance;

    public $wpdb;

    public $background_process;

    public $accepted_methods = array(
		'optimize_tables',
		'draft_page',
		'revisions_page',
		'trash_page',
		'spam_comments',
		'trash_comments',
		'expire_transients',
	);

    public function init() {

        global $wpdb;
		$this->wpdb = $wpdb;
		
		$status_data = next3_service_status();
		$database_status = ($status_data['database']) ?? false;
		if( !$database_status ){
			return;
		}
		
        if (! wp_next_scheduled ( 'next3_database_optimizer' )) {
            wp_schedule_event( time(), 'weekly', 'next3_database_optimizer' );
        }
		// end cron hook
        register_deactivation_hook( next3_core()->plugin::plugin_file(), function(){
            wp_clear_scheduled_hook( 'next3_database_optimizer' );
        } );
		
		add_action( 'next3_database_optimizer', [ $this, 'optimize_database'] );
    }

    public function optimize_database() {
		
		$settings_options = next3_options();
		$optimization = ($settings_options['optimization']) ?? [];

        $enable_database = ($settings_options['optimization']['database']) ?? 'no';
		if( $enable_database != 'yes'){
			return;
		}
		
		// Bail if the methods array is empty.
		if ( empty( $optimization ) ) {
			return;
		}

		// Check if the methods in the db match the ones we expect.
		foreach ( $this->accepted_methods as $method ) {
			// Skip method if not allowed.
			
			if( !isset( $optimization[ $method ] )){
				continue;
			}
			if( method_exists(__CLASS__, $method)){
				call_user_func( array( __CLASS__, $method ) );
			}
		}
	}

    /**
	 * Delete all auto-drafts.
	 *
	 * @since  3.0.11
	 */
	public function draft_page() {
		// Get the auto-drafts of posts.
		$posts = $this->wpdb->get_col( "SELECT ID FROM " . $this->wpdb->posts . " WHERE post_status = 'auto-draft'" );

		// Bail if there are no posts.
		if ( ! $posts ) {
			return;
		}

		// Loop trough the result and delete the auto-draft.
		foreach ( $posts as $id ) {
			wp_delete_post( intval( $id ), true );
		}
	}

	/**
	 * Delete all post revisions
	 *
	 * @since  3.0.11
	 */
	public function revisions_page() {
		// Get all posts revisions.
		$posts = $this->wpdb->get_col( "SELECT ID FROM " . $this->wpdb->posts . " WHERE post_type = 'revision'" );

		// Bail if there are no posts.
		if ( ! $posts ) {
			return;
		}

		// Loop trough result and delete post revisions.
		foreach ( $posts as $id ) {
			wp_delete_post_revision( intval( $id ) );
		}
	}

	/**
	 * Delete all trashed posts.
	 *
	 * @since  3.0.11
	 */
	public function trash_page() {
		// Get all trashed posts.
		$posts = $this->wpdb->get_col( "SELECT ID FROM " . $this->wpdb->posts . " WHERE post_status = 'trash'" );

		// Bail if there are no posts.
		if ( ! $posts ) {
			return;
		}

		// Loop trough result and delete trashed posts.
		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}
	}

	/**
	 * Delete all spam comments.
	 *
	 * @since  3.0.11
	 */
	public function spam_comments() {
		// Get all spam comments.
		$comments = $this->wpdb->get_col( "SELECT comment_ID FROM " . $this->wpdb->comments . " WHERE comment_approved = 'spam'" );

		// Bail if there are no comments.
		if ( ! $comments ) {
			return;
		}

		// Loop trough result and delete spam comments.
		foreach ( $comments as $id ) {
			wp_delete_comment( intval( $id ), true );
		}
	}

	/**
	 * Delete trashed comments.
	 *
	 * @since  3.0.11
	 */
	public function trash_comments() {
		// Get all trashed comments.
		$comments = $this->wpdb->get_col( "SELECT comment_ID FROM " . $this->wpdb->comments . " WHERE comment_approved = 'trash'" );

		// Bail if there are no comments.
		if ( ! $comments ) {
			return;
		}

		// Loop trough and delete trashed comments.
		foreach ( $comments as $id ) {
			wp_delete_comment( intval( $id ), true );
		}
	}

	/**
	 * Delete expired transients.
	 *
	 * @since  3.0.11
	 */
	public function expire_transients() {
		$time  = isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time();
		$transients = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT option_name FROM " . $this->wpdb->options . " WHERE option_name LIKE %s AND option_value < %d", $this->wpdb->esc_like( '_transient_timeout' ) . '%', $time ) );

		// Bail if there are no transients.
		if ( ! $transients ) {
			return;
		}

		// Loop trough and delete expired transients.
		foreach ( $transients as $transient ) {
			delete_transient( str_replace( '_transient_timeout_', '', $transient ) );
		}
	}

	/**
	 * Optimize database tables
	 *
	 * @since  3.0.11
	 */
	public function optimize_tables() {
		$tables = $this->wpdb->get_results( "SELECT table_name, data_free FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' and Engine <> 'InnoDB' and data_free > 0" );

		// Bail if there are no results.
		if ( ! $tables ) {
			return;
		}

		// Add filter to skip specific tables from being optimized.
		$excluded_tables = apply_filters( 'next3_db_optimization_exclude', array() );

		// Add prefixes to all tables.
		$prefix_tables = preg_filter( '/^/', $this->wpdb->prefix, $excluded_tables );

		// Get the tables without prefix.
		$diffs = array_diff( $excluded_tables, $prefix_tables );

		// Merge user input and prefixed tables if custom tables are skipped.
		$all_tables = array_merge( $diffs, $prefix_tables );

		// Loop trough and optimize table.
		foreach ( $tables as $table ) {
			// Check if we need to skip that specific table from the optimization.
			if ( in_array( $table->table_name, $all_tables ) ) {
				continue;
			}

			$this->wpdb->query( "OPTIMIZE TABLE $table->table_name" );
		}
	}

    public static function instance(){
        if (!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
}