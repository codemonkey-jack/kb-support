<?php
/**
 * Setup Knowledgebase
 *
 * @package     KBS
 * @subpackage  Classes/Knowledgebase
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Knowledgebase Class
 *
 * @since	1.0.8
 */
class KBS_Knowledgebase {

	/**
	 * Registered knowledgebases
	 *
	 * @since	1.0.8
	 * @var		array
	 */
	public $registered_integrations;

	/**
	 * Default knowledgebase?
	 *
	 * @since	1.0.8
	 *
	 */
	public $default_kb = true;

	/**
	 * The article post type
	 *
	 * @since	1.0.8
	 */
	public $post_type = 'kbs_article';

	/**
	 * The active knowledgebase
	 *
	 * @since	1.0.8
	 */
	public $active_kb;

	/**
	 * Get things going
	 *
	 * @since	1.0.8
	 */
	public function __construct()	{
		add_action( 'init',                   array( $this, 'setup_kb' ) );
		add_action( 'init',                   array( $this, 'register_meta' ) );
		add_action( 'kbs_kb_init_kb-support', array( $this, 'register_post_type' ) );
		add_action( 'kbs_kb_init_kb-support', array( $this, 'register_taxonomies' ), 5 );
	} // __construct

	/**
	 * Setup the knowledgebase.
	 *
	 * @since	1.0.8
	 */
	public function setup_kb()	{
		$this->get_registered_knowledgebases();
		$this->get_active_kb();

		if ( 'kb-support' != $this->active_kb )	{
			$this->default_kb = false;
		}

		do_action( 'kbs_kb_init_' . $this->active_kb, $this );
	} // setup_kb

	/**
	 * Retrieve the registered KBs.
	 *
	 * @since	1.0.8
	 */
	public function get_registered_knowledgebases()	{
		if ( ! isset( $this->registered_integrations ) )	{
			$this->registered_integrations = array(
				'kb-support' => __( 'KB Support', 'kb-support' )
			);
		}

		// Allow devs to register knowledgebases
		$this->registered_integrations = apply_filters( 'kbs_registered_kb_integrations', $this->registered_integrations );

		asort( $this->registered_integrations );

		return $this->registered_integrations;
	} // get_registered_knowledgebases

	/**
	 * Retrieve the active KB.
	 *
	 * @since	1.0.8
	 */
	public function get_active_kb()	{
		if ( ! isset( $this->active_kb ) )	{
			$this->active_kb = apply_filters( 'kbs_active_kb', 'kb-support' );
		}

		return $this->active_kb;
	} // get_active_kb

	/**
	 * Register the default KB Article post type.
	 *
	 * @since	1.0.8
	 * @return	void
	 */
	public function register_post_type()	{
		$article_archives = defined( 'KBS_ARTICLE_DISABLE_ARCHIVE' ) && KBS_ARTICLE_DISABLE_ARCHIVE ? false : true;
		$articles_slug    = defined( 'KBS_ARTICLE_SLUG' ) ? KBS_ARTICLE_SLUG : 'articles';
		$articles_rewrite = defined( 'KBS_ARTICLE_DISABLE_REWRITE' ) && KBS_ARTICLE_DISABLE_REWRITE ? false : array( 'slug' => $articles_slug, 'with_front' => false );

		$article_labels = array(
			'name'               => _x( '%2$s', 'article type general name', 'kb-support' ),
			'singular_name'      => _x( '%1$s', 'article type singular name', 'kb-support' ),
			'add_new'            => __( 'New %1$s', 'kb-support' ),
			'add_new_item'       => __( 'New %1$s', 'kb-support' ),
			'edit_item'          => __( 'Edit %1$s', 'kb-support' ),
			'new_item'           => __( 'New %1$s', 'kb-support' ),
			'all_items'          => __( '%2$s', 'kb-support' ),
			'view_item'          => __( 'View %1$s', 'kb-support' ),
			'search_items'       => __( 'Search %2$s', 'kb-support' ),
			'not_found'          => __( 'No %2$s found', 'kb-support' ),
			'not_found_in_trash' => __( 'No %2$s found in Trash', 'kb-support' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( '%2$s', 'kb-support' )
		);

		foreach ( $article_labels as $key => $value ) {
			$article_labels[ $key ] = sprintf( $value, kbs_get_article_label_singular(), kbs_get_article_label_plural() );
		}

		$article_args = array(
			'labels'                => $article_labels,
			'public'                => true,
			'show_in_menu'          => true,
			'menu_icon'             => 'dashicons-welcome-learn-more',
			'query_var'             => true,
			'rewrite'               => $articles_rewrite,
			'capability_type'       => 'article',
			'map_meta_cap'          => true,
			'has_archive'           => $article_archives,
			'hierarchical'          => false,
			'supports'              => apply_filters( 'kbs_article_supports', array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'trackbacks', 'comments', 'custom-fields' ) ),
			'can_export'            => true,
            'show_in_rest'          => true,
			'rest_base'             => 'articles',
			'rest_controller_class' => 'KBS_Articles_API'
		);

		register_post_type( 'kbs_article', $article_args );
	} // register_post_type

	/**
	 * Register the default KB Article taxonomies.
	 *
	 * @since	1.0.8
	 * @return	void
	 */
	public function register_taxonomies()	{
		$articles_slug = defined( 'KBS_ARTICLE_SLUG' ) ? KBS_ARTICLE_SLUG : 'articles';

		$article_category_labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'kb-support' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'kb-support' ),
			'search_items'      => sprintf( __( 'Search %s Categories', 'kb-support' ), kbs_get_article_label_singular() ),
			'all_items'         => sprintf( __( 'All %s Categories', 'kb-support' ), kbs_get_article_label_singular() ),
			'parent_item'       => sprintf( __( 'Parent %s Category', 'kb-support' ), kbs_get_article_label_singular() ),
			'parent_item_colon' => sprintf( __( 'Parent %s Category:', 'kb-support' ), kbs_get_article_label_singular() ),
			'edit_item'         => sprintf( __( 'Edit %s Category', 'kb-support' ), kbs_get_article_label_singular() ),
			'update_item'       => sprintf( __( 'Update %s Category', 'kb-support' ), kbs_get_article_label_singular() ),
			'add_new_item'      => sprintf( __( 'Add New %s Category', 'kb-support' ), kbs_get_article_label_singular() ),
			'new_item_name'     => sprintf( __( 'New %s Category Name', 'kb-support' ), kbs_get_article_label_singular() ),
			'menu_name'         => __( 'Categories', 'kb-support' )
		);

		$article_category_args = apply_filters( 'kbs_article_category_args', array(
			'hierarchical' => true,
			'labels'       => apply_filters( 'kbs_article_category_labels', $article_category_labels ),
			'show_ui'      => true,
			'query_var'    => 'article_category',
			'rewrite'      => array( 'slug' => $articles_slug . '/category', 'with_front' => false, 'hierarchical' => true ),
			'capabilities' => array(
				'manage_terms' => 'manage_article_terms',
				'edit_terms'   => 'edit_article_terms',
				'assign_terms' => 'assign_article_terms',
				'delete_terms' => 'delete_article_terms'
			),
			'show_in_rest' => true
		) );

		register_taxonomy( 'article_category', array( 'kbs_article' ), $article_category_args );
		register_taxonomy_for_object_type( 'article_category', 'kbs_article' );

		/** Article Tags */
		$article_tag_labels = array(
			'name'                  => _x( 'Tags', 'taxonomy general name', 'kb-support' ),
			'singular_name'         => _x( 'Tag', 'taxonomy singular name', 'kb-support' ),
			'search_items'          => sprintf( __( 'Search %s Tags', 'kb-support' ), kbs_get_article_label_singular() ),
			'all_items'             => sprintf( __( 'All %s Tags', 'kb-support' ), kbs_get_article_label_singular() ),
			'parent_item'           => sprintf( __( 'Parent %s Tag', 'kb-support' ), kbs_get_article_label_singular() ),
			'parent_item_colon'     => sprintf( __( 'Parent %s Tag:', 'kb-support' ), kbs_get_article_label_singular() ),
			'edit_item'             => sprintf( __( 'Edit %s Tag', 'kb-support' ), kbs_get_article_label_singular() ),
			'update_item'           => sprintf( __( 'Update %s Tag', 'kb-support' ), kbs_get_article_label_singular() ),
			'add_new_item'          => sprintf( __( 'Add New %s Tag', 'kb-support' ), kbs_get_article_label_singular() ),
			'new_item_name'         => sprintf( __( 'New %s Tag Name', 'kb-support' ), kbs_get_article_label_singular() ),
			'menu_name'             => __( 'Tags', 'kb-support' ),
			'choose_from_most_used' => sprintf( __( 'Choose from most used %s tags', 'kb-support' ), kbs_get_article_label_singular() )
		);

		$article_tag_args = apply_filters( 'kbs_article_tag_args', array(
			'hierarchical' => false,
			'labels'       => apply_filters( 'kbs_article_tag_labels', $article_tag_labels ),
			'show_ui'      => true,
			'query_var'    => 'article_tag',
			'rewrite'      => array( 'slug' => $articles_slug . '/tag', 'with_front' => false, 'hierarchical' => true  ),
			'capabilities' => array(
				'manage_terms' => 'manage_article_terms',
				'edit_terms'   => 'edit_article_terms',
				'assign_terms' => 'assign_article_terms',
				'delete_terms' => 'delete_article_terms'
			),
			'show_in_rest' => true
		) );

		register_taxonomy( 'article_tag', array( 'kbs_article' ), $article_tag_args );
		register_taxonomy_for_object_type( 'article_tag', 'kbs_article' );
	} // register_taxonomies

	/**
	 * Retrieve the meta keys for this post type.
	 *
	 * Array format: key = meta_name, value = $args (see register_meta)
	 *
	 * @since	1.5
	 * @return	array	Array of meta key parameters
	 */
	public function get_meta_fields()	{
		$object = get_post_type_object( $this->post_type );
		
		$meta_fields = array(
			'_kbs_article_restricted' => array(
				'type'              => 'integer',
				'description'       => sprintf(
					__( 'Specifies whether or not the %s is restricted to logged in users only.', 'kb-support' ),
					kbs_get_article_label_singular( true )
				),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'integer'
					)
				)
			),
			kbs_get_article_view_count_meta_key_name() => array(
				'type'              => 'integer',
				'description'       => sprintf(
					__( 'Total number of all time views for this %s.', 'kb-support' ),
					kbs_get_article_label_singular( true )
				),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'integer'
					)
				)
			),
			kbs_get_article_view_count_meta_key_name( false ) => array(
				'type'              => 'integer',
				'description'       => sprintf(
					__( 'Current monthly total number of times this %s has been viewed.', 'kb-support' ),
					kbs_get_article_label_singular( true )
				),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'integer'
					)
				)
			)
		);

		$meta_fields = apply_filters( "kbs_{$this->post_type}_meta_fields", $meta_fields, $this->active_kb );

		return $meta_fields;
	} // get_meta_fields

	/**
	 * Register meta fields.
	 *
	 * @since	1.5
	 * @return	void
	 */
	public function register_meta()	{
		$fields = $this->get_meta_fields();

		foreach( $fields as $key => $args )	{
			register_post_meta( $this->post_type, $key, $args );
		}
	} // register_meta

} // KBS_Knowledgebase
