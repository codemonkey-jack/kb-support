<?php
/**
 * HTML elements
 *
 * A helper class for outputting common HTML elements.
 *
 * @package     KBS
 * @subpackage  Classes/HTML
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_HTML_Elements Class
 *
 * @since	1.0
 */
class KBS_HTML_Elements {

	/**
	 * Renders an HTML Dropdown of all the Ticket Post Statuses
	 *
	 * @access	public
	 * @since	1.0
	 * @param	str		$name		Name attribute of the dropdown
	 * @param	str		$selected	Status to select automatically
	 * @return	str		$output		Status dropdown
	 */
	public function ticket_status_dropdown( $args ) {

        $defaults = array(
			'name'             => 'post_status',
			'id'               => '',
			'class'            => '',
			'multiple'         => false,
			'chosen'           => false,
			'show_option_all'  => false,
			'show_option_none' => false,
			'placeholder'      => sprintf( __( 'Select a %s', 'kb-support' ), kbs_get_ticket_label_singular() ),
			'selected'         => 0,
			'data'        => array(
				'search-type'        => 'ticket_status',
				'search-placeholder' => sprintf( __( 'Type to search all %s statuses', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
			)
		);

		$args = wp_parse_args( $args, $defaults );

		$ticket_statuses = kbs_get_ticket_statuses();
		$options         = array();
		
		foreach ( $ticket_statuses as $ticket_status => $ticket_label ) {
			$options[ $ticket_status ] = esc_html( $ticket_label );
		}

		$options = apply_filters( 'kbs_ticket_status_dropdown_options', $options, $args );

		$output = $this->select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'options'          => $options,
            'multiple'         => $args['multiple'],
            'chosen'           => $args['chosen'],
			'show_option_all'  => $args['show_option_all'],
			'show_option_none' => $args['show_option_none'],
            'placeholder'      => $args['placeholder'],
            'data'             => $args['data'],
            
		) );

		return $output;
	} // ticket_status_dropdown

	/**
	 * Renders an HTML Dropdown of all the Ticket Categories
	 *
	 * @access	public
	 * @since	1.0
	 * @param	str		$name		Name attribute of the dropdown
	 * @param	int		$selected	Category to select automatically
	 * @return	str		$output		Category dropdown
	 */
	public function ticket_category_dropdown( $name = 'kbs_ticket_categories', $selected = 0 ) {
		$categories = get_terms( 'ticket_category', apply_filters( 'kbs_ticket_category_dropdown', array() ) );
		$options    = array();

		foreach ( $categories as $category ) {
			$options[ absint( $category->term_id ) ] = esc_html( $category->name );
		}

		$category_labels = kbs_get_taxonomy_labels( 'ticket_category' );
		$output = $this->select( array(
			'name'             => $name,
			'selected'         => $selected,
			'options'          => $options,
			'show_option_all'  => sprintf( _x( 'All %s', 'plural: Example: "All Categories"', 'kb-support' ), $category_labels['name'] ),
			'show_option_none' => false
		) );

		return $output;
	} // ticket_category_dropdown

    /**
	 * Renders an HTML Dropdown of all the Ticket Sources
	 *
	 * @access	public
	 * @since	1.2.9
	 * @param	string	$name		Name attribute of the dropdown
	 * @param	int		$selected	Category to select automatically
	 * @return	string	$output		Category dropdown
	 */
	public function ticket_source_dropdown( $name = 'kbs_ticket_source', $selected = 0 ) {
		$category_labels = kbs_get_taxonomy_labels( 'ticket_source' );
		$output = $this->select( array(
			'name'             => $name,
			'selected'         => $selected,
			'options'          => kbs_get_ticket_log_sources(),
            'placeholder'      => sprintf( __( 'Select a %s', 'kb-support' ), $category_labels['name'] ),
			'show_option_all'  => false,
			'show_option_none' => sprintf( _x( 'Select a %1$s', 'plural: Example: "Select a %1$s"', 'kb-support' ), $category_labels['name'] ),
            'chosen'           => true,
            'data'             => array(
				'search-type'        => 'ticket_source',
				'search-placeholder' => sprintf( __( 'Type to search all %s', 'kb-support' ), strtolower( $category_labels['name'] ) )
			)
		) );

		return $output;
	} // ticket_source_dropdown

	/**
	 * Renders an HTML Dropdown of all the KB Articles
	 *
	 * @access	public
	 * @since	1.0
	 * @param	arr		$args		Arguments
	 * @return	str		$output		Article dropdown
	 */
	public function article_dropdown( $args = array() ) {

		$defaults = array(
			'name'             => 'kbs_articles',
			'id'               => '',
			'class'            => '',
			'multiple'         => false,
			'chosen'           => false,
			'show_option_all'  => false,
			'show_option_none' => '',
			'placeholder'      => sprintf( __( 'Select a %s', 'kb-support' ), kbs_get_article_label_singular() ),
			'selected'         => 0,
			'data'        => array(
				'search-type'        => 'kbs_article',
				'search-placeholder' => sprintf( __( 'Type to search all %s', 'kb-support' ), kbs_get_article_label_plural() )
			),
			'key'              => 'id',
			'articles'         => null,
			'author'           => null,
			'restricted'       => null,
			'number'           => -1,
			'orderby'          => 'title',
			'order'            => 'ASC'
		);

		$args = wp_parse_args( $args, $defaults );

		$articles = kbs_get_articles( array(
			'articles'    => isset( $args['articles'] )   ? $args['articles']   : null,
			'author'      => isset( $args['author'] )     ? $args['author']     : null,
			'restricted'  => isset( $args['restricted'] ) ? $args['restricted'] : null,
			'number'      => $args['number'],
			'orderby'     => $args['orderby'],
			'order'       => $args['order']
		) );

		$args['options']    = array();
		$args['options'][0] = '';

		if ( ! empty( $articles ) )	{
			foreach( $articles as $article )	{

				switch( $args['key'] )	{
					case 'id':
					case 'ID':
					default:
						$key = (int) $article->ID;
						break;
					case 'url':
						$key = kbs_get_article_url( $article );
						break;
					case 'name':
					case 'slug':
						$key = $article->post_name;
						break;
				}

				$args['options'][ $key  ] = get_the_title( $article );
			}
		}

		unset(
			$args['articles'], $args['author'], $args['restricted'],
			$args['number'], $args['orderby'], $args['order']
		);

		$output = $this->select( $args );

		return $output;
	} // article_dropdown

	/**
	 * Renders an HTML Dropdown of all the KB Categories
	 *
	 * @access	public
	 * @since	1.0
	 * @param	str		$name		Name attribute of the dropdown
	 * @param	int		$selected	Category to select automatically
	 * @return	str		$output		Category dropdown
	 */
	public function article_category_dropdown( $name = 'kbs_article_categories', $selected = 0 ) {
		$categories = get_terms( 'article_category', apply_filters( 'kbs_article_category_dropdown', array() ) );
		$options    = array();

		foreach ( $categories as $category ) {
			$options[ absint( $category->term_id ) ] = esc_html( $category->name );
		}

		$category_labels = kbs_get_taxonomy_labels( 'article_category' );
		$output = $this->select( array(
			'name'             => $name,
			'selected'         => $selected,
			'options'          => $options,
			'show_option_all'  => sprintf( _x( 'All %s', 'plural: Example: "All Categories"', 'kb-support' ), $category_labels['name'] ),
			'show_option_none' => false
		) );

		return $output;
	} // article_category_dropdown

	/**
	 * Renders an HTML Dropdown of years
	 *
	 * @access	public
	 * @since	1.0
	 * @param	str		$name			Name attribute of the dropdown
	 * @param	int		$selected		Year to select automatically
	 * @param	int		$years_before	Number of years before the current year the dropdown should start with
	 * @param	int		$years_after	Number of years after the current year the dropdown should finish at
	 * @return	str		$output			Year dropdown
	 */
	public function year_dropdown( $name = 'year', $selected = 0, $years_before = 5, $years_after = 0 ) {
		$current     = date( 'Y' );
		$start_year  = $current - absint( $years_before );
		$end_year    = $current + absint( $years_after );
		$selected    = empty( $selected ) ? date( 'Y' ) : $selected;
		$options     = array();

		while ( $start_year <= $end_year ) {
			$options[ absint( $start_year ) ] = $start_year;
			$start_year++;
		}

		$output = $this->select( array(
			'name'             => $name,
			'selected'         => $selected,
			'options'          => $options,
			'show_option_all'  => false,
			'show_option_none' => false
		) );

		return $output;
	} // year_dropdown

	/**
	 * Renders an HTML Dropdown of months
	 *
	 * @access	public
	 * @since	1.0
	 * @param	str		$name		Name attribute of the dropdown
	 * @param	int		$selected	Month to select automatically
	 * @return	str		$output		Month dropdown
	 */
	public function month_dropdown( $name = 'month', $selected = 0 ) {
		$month   = 1;
		$options = array();
		$selected = empty( $selected ) ? date( 'n' ) : $selected;

		while ( $month <= 12 ) {
			$options[ absint( $month ) ] = kbs_month_num_to_name( $month );
			$month++;
		}

		$output = $this->select( array(
			'name'             => $name,
			'selected'         => $selected,
			'options'          => $options,
			'show_option_all'  => false,
			'show_option_none' => false
		) );

		return $output;
	} // month_dropdown

	/**
	 * Renders an HTML Dropdown of customers
	 *
	 * @access	public
	 * @since	1.0
	 * @param	arr		$args
	 * @return	str		Customer dropdown
	 */
	public function customer_dropdown( $args = array() ) {

		$defaults = array(
			'name'             => 'customers',
			'id'               => '',
			'class'            => '',
			'multiple'         => false,
			'selected'         => 0,
			'chosen'           => true,
			'company_id'       => null,
			'show_company'     => false,
            'exclude_id'       => null,
			'placeholder'      => __( 'Select a Customer', 'kb-support' ),
			'number'           => -1,
			'show_no_attached' => true,
            'show_option_all'  => false,
            'show_option_none' => __( 'Select a Customer', 'kb-support' ),
			'data'             => array(
				'search-type'        => 'customer',
				'search-placeholder' => __( 'Type to search all customers', 'kb-support' )
			)
		);

		$args = wp_parse_args( $args, $defaults );

		$customers = KBS()->customers->get_customers( array(
			'number'     => $args['number'],
			'company_id' => $args['company_id'],
            'exclude_id' => $args['exclude_id']
		) );

		$options  = array();

		if ( $customers ) {
			if ( $args['show_no_attached'] )	{
				$options[0] = __( 'No customer attached', 'kb-support' );
			}

			foreach ( $customers as $customer ) {
				$company = '';
				if ( $args['show_company'] && ! empty( $customer->company_id ) )	{
					$company =  ' (' . kbs_get_company_name( $customer->company_id ) . ')';
				}
				$options[ absint( $customer->id ) ] = esc_html( $customer->name ) . $company;
			}
		} else {
			$options[0] = __( 'No customers found', 'kb-support' );
		}

		if ( ! empty( $args['selected'] ) ) {

			// If a selected customer has been specified, we need to ensure it's in the initial list of customers displayed

			if ( ! array_key_exists( $args['selected'], $options ) ) {

				$customer = new KBS_Customer( $args['selected'] );

				if ( $customer ) {
					$options[ absint( $args['selected'] ) ] = esc_html( $customer->name );
				}

			}

		}

		$output = $this->select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'] . ' kbs-customer-select',
			'options'          => $options,
			'multiple'         => $args['multiple'],
			'placeholder'      => $args['placeholder'],
			'chosen'           => $args['chosen'],
			'show_option_all'  => $args['show_option_all'],
			'show_option_none' => $args['show_option_none'],
			'data'             => $args['data']
		) );

		return $output;
	} // customer_dropdown

	/**
	 * Renders an HTML Dropdown of all the Users
	 *
	 * @access	public
	 * @since	1.2
	 * @param	arr		$args
     * @param   arr     $user_args
	 * @return	str		$output	User dropdown
	 */
	public function user_dropdown( $args = array(), $user_args = array() ) {

		$defaults = array(
			'name'             => 'users',
			'id'               => 'users',
			'class'            => '',
			'multiple'         => false,
			'selected'         => 0,
			'chosen'           => true,
			'placeholder'      => __( 'Select a User', 'kb-support' ),
			'number'           => -1,
			'show_option_all'  => false,
			'show_option_none' => false,
			'data'             => array(
				'search-type'        => 'user',
				'search-placeholder' => __( 'Type to search all users', 'kb-support' ),
			),
		);

        $user_defaults = array(
            'number' => -1
        );

		$args      = wp_parse_args( $args, $defaults );
        $user_args = wp_parse_args( $user_args, $user_defaults );

		$users   = get_users( $user_args );
		$options = array();

		if ( $users ) {
			foreach ( $users as $user ) {
				$options[ $user->ID ] = esc_html( $user->display_name );
			}
		} else {
			$options[0] = __( 'No users found', 'kb-support' );
		}

		// If a selected user has been specified, we need to ensure it's in the initial list of user displayed
		if( ! empty( $args['selected'] ) ) {

			if( ! array_key_exists( $args['selected'], $options ) ) {

				$user = get_userdata( $args['selected'] );

				if( $user ) {

					$options[ absint( $args['selected'] ) ] = esc_html( $user->display_name );

				}

			}

		}

		$output = $this->select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'] . ' kbs-user-select',
			'options'          => $options,
			'multiple'         => $args['multiple'],
			'placeholder'      => $args['placeholder'],
			'chosen'           => $args['chosen'],
			'show_option_all'  => $args['show_option_all'],
			'show_option_none' => $args['show_option_none'],
			'data'             => $args['data'],
		) );

		return $output;
	} // user_dropdown

	/**
	 * Renders an HTML Dropdown of all the form field types
	 *
	 * @access	public
	 * @since	1.2
	 * @param	arr		$args
	 * @return	str		$output	User dropdown
	 */
	public function field_types_dropdown( $args = array() ) {

		$defaults = array(
			'name'             => 'kbs_field_type',
			'id'               => 'kbs_field_type',
			'class'            => 'kbs_field_type',
			'multiple'         => false,
			'selected'         => 0,
			'chosen'           => true,
			'placeholder'      => __( 'Choose a Field Type', 'kb-support' ),
			'show_option_all'  => false,
			'show_option_none' => __( 'Choose a Field Type', 'kb-support' ),
			'data'             => array(
				'search-type'        => 'fields',
				'search-placeholder' => __( 'Type to search all fields', 'kb-support' ),
			)
		);

		$args = wp_parse_args( $args, $defaults );

		$options = kbs_get_field_types();

		$output = $this->select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'] . ' kbs-user-select',
			'options'          => $options,
			'multiple'         => $args['multiple'],
			'placeholder'      => $args['placeholder'],
			'chosen'           => $args['chosen'],
			'show_option_all'  => $args['show_option_all'],
			'show_option_none' => $args['show_option_none'],
			'data'             => $args['data'],
		) );

		return $output;
	} // field_types_dropdown

	/**
	 * Renders an HTML Dropdown of company's
	 *
	 * @access	public
	 * @since	1.0
	 * @param	arr		$args
	 * @return	str		Company dropdown
	 */
	public function company_dropdown( $args = array() ) {

		$defaults = array(
			'name'             => 'company_id',
			'id'               => '',
			'class'            => '',
			'multiple'         => false,
			'selected'         => 0,
			'chosen'           => true,
			'placeholder'      => __( 'Select a Company', 'kb-support' ),
			'show_option_none' => __( 'No Company', 'kb-support' ),
			'number'           => 30,
			'data'        => array(
				'search-type'        => 'company',
				'search-placeholder' => __( 'Type to search all companies', 'kb-support' )
			)
		);

		$args = wp_parse_args( $args, $defaults );

		$companies = get_posts( array(
			'post_type'      => 'kbs_company',
			'post_status'    => 'publish',
			'posts_per_page' => $args['number'],
			'orderby'        => 'title',
			'order'          => 'ASC'
		) );

		$options  = array();

		if ( $companies ) {
			foreach ( $companies as $company ) {
				$options[ absint( $company->ID ) ] = get_the_title( $company );
			}
		} else {
			$options[0] = __( 'No companies found', 'kb-support' );
		}

		if ( ! empty( $args['selected'] ) ) {

			// If a selected company has been specified, we need to ensure it's in the initial list of companies displayed

			if ( ! array_key_exists( $args['selected'], $options ) ) {

				$company = new KBS_Company( $args['selected'] );

				if ( $company ) {
					$options[ absint( $args['selected'] ) ] = esc_html( $company->name );
				}

			}

		}

		$output = $this->select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'] . ' kbs-company-select',
			'options'          => $options,
			'multiple'         => $args['multiple'],
			'chosen'           => $args['chosen'],
			'placeholder'      => $args['placeholder'],
			'show_option_all'  => false,
			'show_option_none' => $args['show_option_none'],
			'data'             => $args['data']
		) );

		return $output;
	} // company_dropdown

	/**
	 * Renders an HTML Dropdown of agents
	 *
	 * @access	public
	 * @since	1.0
	 * @param	arr		$args
	 * @return	str		$output		Agent dropdown
	 */
	public function agent_dropdown( $args = array() ) {
		$options  = array();

        $defaults = array(
			'options'          => array(),
			'name'             => 'kbs_agent',
			'show_option_all'  => false,
			'show_option_none' => __( 'Select an Agent', 'kb-support' ),
            'exclude'          => array(),
            'selected'         => 0,
            'chosen'           => false,
            'multiple'         => false,
            'placeholder'      => __( 'Select an Agent', 'kb-support' ),
			'data'             => array(
				'search-type'        => 'agent',
				'search-placeholder' => __( 'Type to search all agents', 'kb-support' )
			)
		);

		$args = wp_parse_args( $args, $defaults );

        if ( ! is_array( $args['exclude'] ) )   {
            $args['exclude'] = array( $args['exclude'] );
        }

		$agents = kbs_get_agents();

		if ( $agents )	{
			foreach( $agents as $agent )	{
                if ( in_array( $agent->ID, $args['exclude'] ) ) {
                    continue;
                }
				$options[ $agent->ID ] = $agent->display_name;
			}

            $args['options'] = $options;
		}

		$output = $this->select( $args );

		return $output;
	} // agent_dropdown

	/**
	 * Renders an HTML Dropdown of departments
	 *
	 * @access	public
	 * @since	1.2
	 * @param	arr		$args
	 * @return	str		$output		Agent dropdown
	 */
	public function department_dropdown( $args = array() ) {
		$options  = array();

        $defaults = array(
			'options'          => array(),
			'name'             => 'departments',
			'show_option_all'  => false,
			'show_option_none' => false,
            'exclude'          => array(),
            'selected'         => 0,
            'chosen'           => true,
            'multiple'         => false,
            'placeholder'      => __( 'Select a Department', 'kb-support' ),
			'data'             => array(
				'search-type'        => 'department',
				'search-placeholder' => __( 'Type to search all departments', 'kb-support' )
			)
		);

		$args = wp_parse_args( $args, $defaults );

        if ( ! is_array( $args['exclude'] ) )   {
            $args['exclude'] = array( $args['exclude'] );
        }

		$departments = kbs_get_departments();

		if ( $departments )	{
			foreach( $departments as $department )	{
                if ( in_array( $department->term_id, $args['exclude'] ) ) {
                    continue;
                }
				$options[ $department->term_id ] = $department->name;
			}

            $args['options'] = $options;
		}

		$output = $this->select( $args );

		return $output;
	} // department_dropdown

	/**
	 * Renders an HTML Dropdown
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args
	 *
	 * @return	str
	 */
	public function select( $args = array() ) {
		$defaults = array(
			'options'          => array(),
			'name'             => null,
			'class'            => '',
			'id'               => '',
			'selected'         => 0,
			'chosen'           => false,
			'placeholder'      => null,
			'multiple'         => false,
			'show_option_all'  => _x( 'All', 'all dropdown items', 'kb-support' ),
			'show_option_none' => _x( 'None', 'no dropdown items', 'kb-support' ),
			'data'             => array()
		);

		$args = wp_parse_args( $args, $defaults );
		
		$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

		$data_elements = '';
		foreach ( $args['data'] as $key => $value ) {
			$data_elements .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		if ( $args['multiple'] ) {
			$multiple   = ' MULTIPLE';
			$name_array = '[]';
		} else {
			$multiple   = '';
			$name_array = '';
		}

		if ( $args['chosen'] ) {
			$args['class'] .= ' kbs_select_chosen';
		}

		if ( $args['placeholder'] ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		$class  = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$output = '<select name="' . esc_attr( $args['name'] ) . $name_array . '" id="' . esc_attr( kbs_sanitize_key( str_replace( '-', '_', $args['id'] ) ) ) . '" class="kbs-select ' . $class . '"' . $multiple . ' data-placeholder="' . $placeholder . '"'. $data_elements . '>' . "\r\n";

		if ( $args['show_option_all'] ) {
			if ( $args['multiple'] ) {
				$selected = selected( true, in_array( 0, $args['selected'] ), false );
			} else {
				$selected = selected( $args['selected'], 0, false );
			}
			$output .= '<option value="all"' . $selected . '>' . esc_html( $args['show_option_all'] ) . '</option>' . "\r\n";
		}

		if ( ! empty( $args['options'] ) ) {

			if ( $args['show_option_none'] ) {
				if( $args['multiple'] ) {
					$selected = selected( true, in_array( -1, $args['selected'] ), false );
				} else {
					$selected = selected( $args['selected'], -1, false );
				}
				$output .= '<option value="-1"' . $selected . '>' . esc_html( $args['show_option_none'] ) . '</option>' . "\r\n";
			}

			foreach( $args['options'] as $key => $option ) {

				if ( $args['multiple'] && is_array( $args['selected'] ) ) {
					$selected = selected( true, in_array( $key, $args['selected'] ), false );
				} else {
					$selected = selected( $args['selected'], $key, false );
				}

				$output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option ) . '</option>' . "\r\n";
			}
		}

		$output .= '</select>' . "\r\n";

		return $output;
	} // select

	/**
	 * Renders an HTML Checkbox
	 *
	 * @since	1.0
	 * @param	arr		$args
	 * @return	str
	 */
	public function checkbox( $args = array() ) {
		$defaults = array(
			'name'     => null,
			'current'  => null,
			'class'    => 'kbs-checkbox',
			'options'  => array(
				'disabled' => false,
				'readonly' => false
			)
		);

		$args = wp_parse_args( $args, $defaults );

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$options = '';
		if ( ! empty( $args['options']['disabled'] ) ) {
			$options .= ' disabled="disabled"';
		} elseif ( ! empty( $args['options']['readonly'] ) ) {
			$options .= ' readonly';
		}

		$output = '<input type="checkbox"' . $options . ' name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['name'] ) . '" class="' . $class . ' ' . esc_attr( $args['name'] ) . '" value="1"' . checked( 1, $args['current'], false ) . ' />';

		return $output;
	} // checkbox
	
	/**
	 * Renders an HTML Checkbox List
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args
	 *
	 * @return	string
	 */
	public function checkbox_list( $args = array() ) {
		$defaults = array(
			'name'      => null,
			'class'     => 'kbs-checkbox',
			'label_pos' => 'before',
			'options'   => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );

		$label_pos = isset( $args['label_pos'] ) ? $args['label_pos'] : 'before';

		$output = '';
		
		if ( ! empty( $args['options'] ) )	{

			$i = 0;

			foreach( $args['options'] as $key => $value )	{

				if ( $label_pos == 'before' )	{
					$output .= $value . '&nbsp';
				}

				$output .= '<input type="checkbox" name="' . esc_attr( $args['name'] ) . '[]" id="' . esc_attr( $args['name'] ) . '-' . $key . '" class="' . $class . ' ' . esc_attr( $args['name'] ) . '" value="' . $key . '" />';

				if ( $label_pos == 'after' )	{
					$output .= '&nbsp' . $value;
				}

				if ( $i < count( $args['options'] ) )	{
					$output .= '<br />';
				}

				$i++;

			}
			
		}

		return $output;
	} // checkbox_list
	
	/**
	 * Renders HTML Radio Buttons
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args
	 *
	 * @return	string
	 */
	public function radio( $args = array() ) {
		$defaults = array(
			'name'     => null,
			'current'  => null,
			'class'    => 'kbs-radio',
			'label_pos' => 'before',
			'options'  => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );

		$output = '';
		
		if ( ! empty( $args['options'] ) )	{

			$i = 0;

			foreach( $args['options'] as $key => $value )	{

				if ( $args['label_pos'] == 'before' )	{
					$output .= $value . '&nbsp';
				}

                $checked = checked( $args['current'], $key, false );

				$output .= '<input type="radio" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['name'] ) . '-' . $key . '" class="' . $class . ' ' . esc_attr( $args['name'] ) . '" value="' . $key . '"' . $checked . ' />';

				if ( $args['label_pos'] == 'after' )	{
					$output .= '&nbsp' . $value;
				}

				if ( $i < count( $args['options'] ) )	{
					$output .= '<br />';
				}

				$i++;

			}
			
		}

		return $output;
	} // radio

	/**
	 * Renders an HTML Text field
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args	Arguments for the text field
	 * @return	str		Text field
	 */
	public function text( $args = array() ) {

		$defaults = array(
			'id'           => '',
			'name'         => isset( $name )  ? $name  : 'text',
			'value'        => isset( $value ) ? $value : null,
			'label'        => isset( $label ) ? $label : null,
			'desc'         => isset( $desc )  ? $desc  : null,
			'placeholder'  => '',
			'class'        => 'regular-text',
			'disabled'     => false,
			'autocomplete' => '',
			'data'         => false
		);

		$args = wp_parse_args( $args, $defaults );
		
		$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$disabled = '';
		if( $args['disabled'] ) {
			$disabled = ' disabled="disabled"';
		}

		$data = '';
		if ( ! empty( $args['data'] ) ) {
			foreach ( $args['data'] as $key => $value ) {
				$data .= 'data-' . kbs_sanitize_key( $key ) . '="' . esc_attr( $value ) . '" ';
			}
		}

		$output = '<span id="kbs-' . kbs_sanitize_key( $args['name'] ) . '-wrap">';

			$output .= '<label class="kbs-input-label" for="' . kbs_sanitize_key( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label>';

			if ( ! empty( $args['desc'] ) ) {
				$output .= '<span class="kbs-description">' . esc_html( $args['desc'] ) . '</span>';
			}

			$output .= '<input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] )  . '" autocomplete="' . esc_attr( $args['autocomplete'] )  . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="' . $class . '" ' . $data . '' . $disabled . '/>';

		$output .= '</span>';

		return $output;
	} // text

	/**
	 * Renders a date picker
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args	Arguments for the text field
	 * @return	str		Datepicker field
	 */
	public function date_field( $args = array() ) {

		if( empty( $args['class'] ) ) {
			$args['class'] = 'kbs_datepicker';
		} elseif( ! strpos( $args['class'], 'kbs_datepicker' ) ) {
			$args['class'] .= ' kbs_datepicker';
		}

		return $this->text( $args );
	} // date_field

	/**
	 * Renders an HTML textarea
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args	Arguments for the textarea
	 * @return	srt		textarea
	 */
	public function textarea( $args = array() ) {
		$defaults = array(
			'name'        => 'textarea',
			'value'       => null,
			'label'       => null,
			'placeholder' => null,
			'desc'        => null,
			'class'       => 'large-text',
			'disabled'    => false,
			'rows'        => null,
			'cols'        => null
		);

		$args = wp_parse_args( $args, $defaults );

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$disabled = '';

		if( $args['disabled'] ) {
			$disabled = ' disabled="disabled"';
		}
		
		$placeholder = '';
		if( $args['placeholder'] ) {
			$placeholder = ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		}

		$rows = '';
		if ( ! empty( $args['rows'] ) )	{
			$rows = ' rows="' . $args['rows'] . '"';
		}

		$cols = '';
		if ( ! empty( $args['cols'] ) )	{
			$rows = ' cols="' . $args['cols'] . '"';
		}

		$output = '<span id="kbs-' . kbs_sanitize_key( $args['name'] ) . '-wrap">';

			$output .= '<label class="kbs-input-label" for="' . kbs_sanitize_key( $args['name'] ) . '">' . esc_html( $args['label'] ) . '</label>';

			$output .= '<textarea name="' . esc_attr( $args['name'] ) . '" id="' . kbs_sanitize_key( $args['name'] ) . '" class="' . $class . '"' . $rows . $cols . $disabled . $placeholder . '>' . esc_attr( $args['value'] ) . '</textarea>';

			if ( ! empty( $args['desc'] ) ) {
				$output .= '<span class="description">' . esc_html( $args['desc'] ) . '</span>';
			}

		$output .= '</span>';

		return $output;
	} // textarea
	
	/**
	 * Renders an HTML Number field
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args	Arguments for the text field
	 * @return	str		Text field
	 */
	public function number( $args = array() ) {

		$defaults = array(
			'id'           => '',
			'name'         => isset( $name )  ? $name  : 'text',
			'value'        => isset( $value ) ? $value : null,
			'label'        => isset( $label ) ? $label : null,
			'desc'         => isset( $desc )  ? $desc  : null,
			'placeholder'  => '',
			'class'        => 'small-text',
			'min'          => '',
			'max'          => '',
			'disabled'     => false,
			'autocomplete' => '',
			'data'         => false
		);

		$args = wp_parse_args( $args, $defaults );
		
		$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

		$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $args['class'] ) ) );
		$disabled = '';
		if( $args['disabled'] ) {
			$disabled = ' disabled="disabled"';
		}

		$data = '';
		if ( ! empty( $args['data'] ) ) {
			foreach ( $args['data'] as $key => $value ) {
				$data .= 'data-' . kbs_sanitize_key( $key ) . '="' . esc_attr( $value ) . '" ';
			}
		}
		
		$min = ! empty( $args['min'] ) ? ' min="' . $args['min'] . '"' : '';
		$max = ! empty( $args['max'] ) ? ' max="' . $args['max'] . '"' : '';
		
		if ( $max > 5 )	{
			$max = 5;
		}

		$output = '<span id="kbs-' . kbs_sanitize_key( $args['name'] ) . '-wrap">';

			$output .= '<label class="kbs-input-label" for="' . kbs_sanitize_key( $args['id'] ) . '">' . esc_html( $args['label'] ) . '</label>';

			if ( ! empty( $args['desc'] ) ) {
				$output .= '<span class="kbs-description">' . esc_html( $args['desc'] ) . '</span>';
			}

			$output .= '<input type="number" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] )  . '" autocomplete="' . esc_attr( $args['autocomplete'] )  . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="' . $class . '" ' . $data . '' . $min . '' . $max . '' . $disabled . '/>';

		$output .= '</span>';

		return $output;
	} // number
	
	/**
	 * Renders an HTML Hidden field
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args	Arguments for the text field
	 * @return	str		Hidden field
	 */
	public function hidden( $args = array() ) {

		$defaults = array(
			'id'           => '',
			'name'         => isset( $name )  ? $name  : 'hidden',
			'value'        => isset( $value ) ? $value : null
		);

		$args = wp_parse_args( $args, $defaults );
		
		$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

		$output = '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] )  . '" value="' . esc_attr( $args['value'] ) . '" />';

		return $output;
	} // hidden

	/**
	 * Renders an ajax user search field
	 *
	 * @since	1.0
	 *
	 * @param	arr		$args
	 * @return	str		Text field with ajax search
	 */
	public function ajax_user_search( $args = array() ) {

		$defaults = array(
			'name'        => 'user_id',
			'value'       => null,
			'placeholder' => __( 'Enter username', 'kb-support' ),
			'label'       => null,
			'desc'        => null,
			'class'       => '',
			'disabled'    => false,
			'autocomplete'=> 'off',
			'data'        => false
		);

		$args = wp_parse_args( $args, $defaults );

		$args['class'] = 'kbs-ajax-user-search ' . $args['class'];

		$output  = '<span class="kbs_user_search_wrap">';
			$output .= $this->text( $args );
			$output .= '<span class="kbs_user_search_results hidden"><a class="kbs-ajax-user-cancel" title="' . __( 'Cancel', 'kb-support' ) . '" aria-label="' . __( 'Cancel', 'kb-support' ) . '" href="#">x</a><span></span></span>';
		$output .= '</span>';

		return $output;
	} // ajax_user_search

} // KBS_HTML_Elements
