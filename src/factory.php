<?php

namespace Com\Plugish\Libraries\CPT_Factory;

/**
 * Class CPT_Factory
 *
 * @package Com\Plugish\Libraries\CPT_Core
 */
class Factory {

	/**
	 * Holds an instance of the post type.
	 * @var \WP_Post_Type
	 */
	public $type = null;

	/**
	 * Determines if l10n is loaded.
	 * @var bool
	 */
	private $l10n = false;

	/**
	 * An array of CPT arguments.
	 * @var array
	 */
	protected $cpt_args = [];

	/**
	 * The defaults when registering a post type.
	 * @var array
	 */
	protected $post_defaults = [
		'labels'             => [],
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'has_archive'        => true,
		'supports'           => [ 'title', 'editor', 'excerpt' ],
	];

	/**
	 * CPT_Factory constructor.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural   Plural label.
	 * @param string $slug     Custom post type slug.
	 * @param array  $args     Argument overrides.
	 */
	public function __construct( string $singular, string $slug, string $plural = '', array $args = [] ) {
		if ( empty( $singular ) ) {
			throw new \CompileError( esc_html__( 'You must provide a singular label for the post type', 'cpt-factory' ) );
		}

		if ( empty( $slug ) ) {
			throw new \CompileError( 'You must provide a slug for registering a post type' );
		}

		$this->singular = $singular;
		$this->slug     = $slug;
		$this->plural   = $plural ?? $singular;
		$this->args     = $args;
	}

	/**
	 * Helper method to determine if the post type is hierarchical or not.
	 * @return bool
	 */
	private function is_hierarchical() : bool {
		return (bool) $this->args['hierarchical'] ?? false;
	}

	/**
	 * Runs all hooks associated with the class.
	 */
	public function hooks() : void {
		add_action( 'plugins_loaded', [ $this, 'l10n' ], 5 );
		add_action( 'init', [ $this, 'register_post_type' ], 99 );
		add_filter( 'post_updated_messages', [ $this, 'messages' ] );
		add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_messages' ], 10, 2 );
		add_filter( 'manage_edit-' . $this->slug . '_columns', [ $this, 'columns' ] );
		add_filter( 'manage_edit-' . $this->slug . '_sortable_columns', [ $this, 'sortable_columns' ] );
		add_filter( 'enter_title_here', [ $this, 'title' ] );

		// Different column registration for pages/posts
		$h = $this->is_hierarchical() ? 'pages' : 'posts';
		add_action( "manage_{$h}_custom_column", [ $this, 'columns_display' ], 10, 2 );
	}

	/**
	 * Load this library's text domain
	 */
	public function l10n(): void {
		// Only do this one time
		if ( $this->l10n ) {
			return;
		}

		$locale     = apply_filters( 'plugin_locale', get_locale(), 'cpt-factory' );
		$mofile     = dirname( __FILE__ ) . '/languages/cpt-factory-' . $locale . '.mo';
		$this->l10n = load_textdomain( 'cpt-factory', $mofile );
	}

	/**
	 * Returns the CPT arguments for the registered CPT.
	 * @return array
	 */
	protected function get_args(): array {
		if ( ! empty( $this->cpt_args ) ) {
			return $this->cpt_args;
		}

		// Generate CPT labels
		$labels = array(
			'name'                  => $this->plural,
			'singular_name'         => $this->singular,
			// Translators: %s: The post type label.
			'add_new'               => sprintf( esc_html__( 'Add New %s', 'cpt-factory' ), $this->singular ),
			// Translators: %s: The post type label.
			'add_new_item'          => sprintf( esc_html__( 'Add New %s', 'cpt-factory' ), $this->singular ),
			// Translators: %s: The post type label.
			'edit_item'             => sprintf( esc_html__( 'Edit %s', 'cpt-factory' ), $this->singular ),
			// Translators: %s: The post type label.
			'new_item'              => sprintf( esc_html__( 'New %s', 'cpt-factory' ), $this->singular ),
			// Translators: %s: The post type label.
			'all_items'             => sprintf( esc_html__( 'All %s', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'view_item'             => sprintf( esc_html__( 'View %s', 'cpt-factory' ), $this->singular ),
			// Translators: %s: The post type label.
			'search_items'          => sprintf( esc_html__( 'Search %s', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'not_found'             => sprintf( esc_html__( 'No %s', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'not_found_in_trash'    => sprintf( esc_html__( 'No %s found in Trash', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'parent_item_colon'     => $this->is_hierarchical() ? sprintf( esc_html__( 'Parent %s:', 'cpt-factory' ), $this->singular ) : null,
			'menu_name'             => $this->plural,
			// Translators: %s: The post type label.
			'insert_into_item'      => sprintf( esc_html__( 'Insert into %s', 'cpt-factory' ), strtolower( $this->singular ) ),
			// Translators: %s: The post type label.
			'uploaded_to_this_item' => sprintf( esc_html__( 'Uploaded to this %s', 'cpt-factory' ), strtolower( $this->singular ) ),
			// Translators: %s: The post type label.
			'items_list'            => sprintf( esc_html__( '%s list', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'items_list_navigation' => sprintf( esc_html__( '%s list navigation', 'cpt-factory' ), $this->plural ),
			// Translators: %s: The post type label.
			'filter_items_list'     => sprintf( esc_html__( 'Filter %s list', 'cpt-factory' ), strtolower( $this->plural ) )
		);

		$this->cpt_args           = wp_parse_args( $this->args, $this->post_defaults );
		$this->cpt_args['labels'] = wp_parse_args( $this->cpt_args['labels'], $labels );

		return $this->cpt_args;
	}

	/**
	 * Registers the post type with the merged arguments.
	 */
	public function register_post_type(): void {
		$post_type = register_post_type( $this->slug, $this->get_args() );
		if ( is_wp_error( $post_type ) ) {
			throw new \LogicException( $post_type->get_error_message() );
		}

		$this->type = $post_type;
	}


	/**
	 * Registers admin columns to display. To be overridden by an extended class.
	 *
	 * @param array $columns Array of registered column names/labels
	 *
	 * @return array           Modified array
	 */
	public function columns( $columns ): array {
		// placeholder
		return $columns;
	}

	/**
	 * Registers which columns are sortable. To be overridden by an extended class.
	 *
	 * @param array $sortable_columns Array of registered column keys => data-identifier
	 *
	 * @return array           Modified array
	 */
	public function sortable_columns( $sortable_columns ): array {
		// placeholder
		return $sortable_columns;
	}

	/**
	 * Handles admin column display. To be overridden by an extended class.
	 *
	 * @param array $column  Array of registered column names
	 * @param int   $post_id The Post ID
	 */
	public function columns_display( $column, $post_id ):array {
		// placeholder
	}

	/**
	 * Custom bulk actions messages for this post type
	 *
	 * @param array $bulk_messages Array of messages
	 * @param array $bulk_counts   Array of counts under keys 'updated', 'locked', 'deleted', 'trashed' and 'untrashed'
	 *
	 * @return array                  Modified array of messages
	 */
	public function bulk_messages( $bulk_messages, $bulk_counts ): array {
		$bulk_messages[ $this->slug ] = array(
			'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'cpt-factory' ), $bulk_counts['updated'], $this->singular, $this->plural ),
			'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'cpt-factory' ), $bulk_counts['locked'], $this->singular, $this->plural ),
			'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'cpt-factory' ), $bulk_counts['deleted'], $this->singular, $this->plural ),
			'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'cpt-factory' ), $bulk_counts['trashed'], $this->singular, $this->plural ),
			'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'cpt-factory' ), $bulk_counts['untrashed'], $this->singular, $this->plural ),
		);

		return $bulk_messages;
	}

	/**
	 * Override custom post type messages.
	 *
	 * @param array $messages The original messages from core.
	 *
	 * @return array
	 */
	public function messages( array $messages ) : array {
		global $post, $post_ID;

		$cpt_messages = array(
			0 => '', // Unused. Messages start at index 1.
			2 => esc_html__( 'Custom field updated.', 'cpt-factory' ),
			3 => esc_html__( 'Custom field deleted.', 'cpt-factory' ),
			4 => sprintf( esc_html__( '%1$s updated.', 'cpt-factory' ), $this->singular ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( esc_html__( '%1$s restored to revision from %2$s', 'cpt-factory' ), $this->singular, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			7 => sprintf( esc_html__( '%1$s saved.', 'cpt-factory' ), $this->singular ),
		);

		if ( $this->cpt_args['public'] ) {

			$cpt_messages[1] = sprintf( esc_html__( '%1$s updated. <a href="%2$s">View %1$s</a>', 'cpt-factory' ), $this->singular, esc_url( get_permalink( $post_ID ) ) );
			$cpt_messages[6] = sprintf( esc_html__( '%1$s published. <a href="%2$s">View %1$s</a>', 'cpt-factory' ), $this->singular, esc_url( get_permalink( $post_ID ) ) );
			$cpt_messages[8] = sprintf( esc_html__( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>', 'cpt-factory' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );
			// translators: Publish box date format, see http://php.net/date
			$cpt_messages[9]  = sprintf( esc_html__( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>', 'cpt-factory' ), $this->singular, date_i18n( esc_html__( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) );
			$cpt_messages[10] = sprintf( esc_html__( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'cpt-factory' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) );

		} else {

			$cpt_messages[1] = sprintf( esc_html__( '%1$s updated.', 'cpt-factory' ), $this->singular );
			$cpt_messages[6] = sprintf( esc_html__( '%1$s published.', 'cpt-factory' ), $this->singular );
			$cpt_messages[8] = sprintf( esc_html__( '%1$s submitted.', 'cpt-factory' ), $this->singular );
			// translators: Publish box date format, see http://php.net/date
			$cpt_messages[9]  = sprintf( esc_html__( '%1$s scheduled for: <strong>%2$s</strong>.', 'cpt-factory' ), $this->singular, date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) );
			$cpt_messages[10] = sprintf( esc_html__( '%1$s draft updated.', 'cpt-factory' ), $this->singular );

		}

		$messages[ $this->slug ] = $cpt_messages;

		return $messages;
	}

	/**
	 * Filter CPT title entry placeholder text
	 *
	 * @param string $title Original placeholder text
	 *
	 * @return string        Modified placeholder text
	 */
	public function title( $title ): string {

		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $screen->post_type == $this->slug ) {
			return sprintf( esc_html__( '%s Title', 'cpt-factory' ), $this->singular );
		}

		return $title;
	}

	/**
	 * A magic method to get the post type slug if necessary.
	 *
	 * @return string
	 */
	public function __toString() : string {
		return $this->slug;
	}
}
