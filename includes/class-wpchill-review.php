<?php 

/**
 * Main Class for review
 */
class Wpchill_Review {

    private $value;
    private $messages;
    private $link = 'https://wordpress.org/support/plugin/%s/reviews/#new-post';
    private $slug;
	private $plugin_name;
	private $option_name;
	private $textdomain;

    /**
     * Main construct function for our class
     * 
     * @param array $args
     * 
     * @return void
     * 
     */
    function __construct( $args ) {
		if( !isset( $args['slug'] ) && !isset( $args['plugin_name'] ) ) {
			return;
		}

		$this->slug        = $args['slug'];
		$this->plugin_name = $args['plugin_name'];

		if( isset( $args['option_name'] ) ) {
			$this->option_name = $args['option_name'];
		}

		if( isset( $args['textdomain'] ) ) {
			$this->textdomain = $args['textdomain'];
		} else {
			$this->textdomain = $args['slug'];
		}

		$this->messages = array(
			'notice'  => esc_html__( "Hi there! Stoked to see you're using {$this->plugin_name} for a few days now - hope you like it! And if you do, please consider rating it. It would mean the world to us.  Keep on rocking!", $this->textdomain ),
			'rate'    => esc_html__( 'Rate the plugin', $this->textdomain ),
			'rated'   => esc_html__( 'Remind me later', $this->textdomain ),
			'no_rate' => esc_html__( 'Don\'t show again', $this->textdomain ),
		);

		if ( isset( $args['messages'] ) ) {
			$this->messages = wp_parse_args( $args['messages'], $this->messages );
		}

		add_action( 'init', array( $this, 'init' ) );

	}

    public function init() {
        
        if( ! is_admin() ) {
            return;
        }

        $this->value = $this->value();

        if( $this->check() ) {
			add_action( 'admin_notices', array( $this, 'five_star_wp_rate_notice') );
			add_action( "wp_ajax_epsilon_{$this->slug}_review", array( $this, 'ajax' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
        }

    }

    /**
     * Check user capability
     */
	private function check() {

		if ( ! current_user_can('manage_options') ) {
			return false;
		}

		return( time() > $this->value );

	}

    /**
     * Create value entry in db 
     */
    private function value() {

		if( isset( $this->option_name) ) {
        	$value = get_option( $this->option_name );
		} else {
			$value = get_option( "{$this->slug}-rate-time" );
		}
        
        if( $value ) {
            return $value;
        }

		$value = time() + DAY_IN_SECONDS;
		
		if( isset( $this->option_name ) ) {
			update_option( $this->option_name, $value, false);
		}else {
			update_option( "{$this->slug}-rate-time", $value, false);
		}
    }


    /**
     * HTML markup for review class
     */
    public function five_star_wp_rate_notice() {

        $url = sprintf( $this->link, $this->slug );
		?>
		<div id="<?php echo esc_attr($this->slug) ?>-epsilon-review-notice" class="notice notice-success is-dismissible" style="margin-top:30px;">
			<p><?php echo sprintf( esc_html( $this->messages['notice'] ), $this->value ) ; ?></p>
			<p class="actions">
				<a id="epsilon-rate" href="<?php echo esc_url( $url ) ?>" target="_blank" class="button button-primary epsilon-review-button">
					<?php echo esc_html( $this->messages['rate'] ); ?>
				</a>
				<a id="epsilon-later" href="#" style="margin-left:10px" class="epsilon-review-button"><?php echo esc_html( $this->messages['rated'] ); ?></a>
				<a id="epsilon-no-rate" href="#" style="margin-left:10px" class="epsilon-review-button"><?php echo esc_html( $this->messages['no_rate'] ); ?></a>
			</p>
		</div>
		<?php
    }

    /**
     * Ajax action for review
     */
    public function ajax() {
        
        check_ajax_referer( "epsilon-{$this->slug}-review", 'security');

        if( ! isset( $_POST['check'] ) ) {
            wp_die( 'ok' );
        }

		if( isset( $this->option_name) ) {
        	$time = get_option( $this->option_name );
		} else {
			$time = get_option( "{$this->slug}-rate-time" );
		}

		if ( 'epsilon-rate' == $_POST['check'] ) {
			$time = time() + YEAR_IN_SECONDS * 5;
		}elseif ( 'epsilon-later' == $_POST['check'] ) {
			$time = time() + WEEK_IN_SECONDS;
		}elseif ( 'epsilon-no-rate' == $_POST['check'] ) {
			$time = time() + YEAR_IN_SECONDS * 5;
        }
        
		if( isset( $this->option_name ) ) {
			update_option( $this->option_name, $time, false);
		}else {
			update_option( "{$this->slug}-rate-time", $time, false);
		}
		wp_die( 'ok' );
    }

	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	public function ajax_script() {

		$ajax_nonce = wp_create_nonce( "epsilon-{$this->slug}-review" );

		?>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ){

				$( '.epsilon-review-button' ).click( function( evt ){
					var href = $(this).attr('href'),
						id = $(this).attr('id');

					if ( 'epsilon-rate' != id ) {
						evt.preventDefault();
					}

					var data = {
						action: `epsilon_<?php echo $this->slug ?>_review`,
						security: '<?php echo $ajax_nonce; ?>',
						check: id
					};

					if ( 'epsilon-rated' === id ) {
						data['epsilon-review'] = 1;
					}

					$.post( '<?php echo admin_url( 'admin-ajax.php' ) ?>', data, function( response ) {
						$( '#<?php echo $this->slug ?>-epsilon-review-notice' ).slideUp( 'fast', function() {
							$( this ).remove();
						} );
					});

				} );

			});
		</script>

		<?php
	}
}

/**
 * @param array 
 * 
 * Must contain slug && plugin_name (is required to create the proper defaults)
 * Optional components ( if no plugin name is passed we will take the name from the slug)
 * option_name
 * messages - if no messages passed, we have defaults 
 */
new Wpchill_Review( array( 'slug' => 'kb-support', 'plugin_name' => 'KB Support'));