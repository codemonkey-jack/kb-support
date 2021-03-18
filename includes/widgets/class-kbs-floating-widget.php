<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

class KBS_Floating_Widget {

	/**
	 * Holds the class object.
	 *
	 * @since 1.6.0
	 *
	 * @var object
	 */
	public static $instance;

	public $settings;


	/**
	 * Primary class constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct(){

		$this->settings = get_option( 'kbs_settings' );

		add_action( 'wp_footer', array( $this, 'floating_widget' ) );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The KBS_Floating_Widget object.
	 * @since 1.6.0
	 *
	 */
	public static function get_instance(){

		if ( !isset( self::$instance ) && !( self::$instance instanceof KBS_Floating_Widget ) ){
			self::$instance = new KBS_Floating_Widget();
		}

		return self::$instance;

	}

	/**
	 * Our floating widget CSS
	 *
	 * @since 1.6.0
	 */
	public function widget_css(){
		?>
		<style>

			#kbs-beacon {
				position : fixed;
				width    : 60px;
				height   : 60px;
				bottom   : 60px;
				outline  : none;
				padding  : 0;
				right    : 60px;
				z-index  : 9999;
			}

			#kbs-beacon .kbs-beacon-wrapper {
				width            : 100%;
				height           : 100%;
				border-radius    : 30px;
				background-color : rgb(31 171 230);
				text-align       : center;
			}

			#kbs-beacon .kbs-beacon-toggle__input[type=checkbox] {
				transition : none;
				height     : 100%;
				width      : 100%;
				left       : 0;
				top        : 0;
				margin     : 0;
				padding    : 0;
				opacity    : 0;
				position   : absolute;
				z-index    : 9;
			}

			#kbs-beacon .kbs-beacon-close {
				position  : relative;
				display   : none;
				font-size : 26px;
				color     : #fff;
				top       : 17px;
			}

			#kbs-beacon .kbs-beacon-icon {
				position  : relative;
				display   : inline-block;
				font-size : 26px;
				color     : #fff;
				top       : 17px;
			}

			.kbs-beacon-content {
				bottom           : 160px;
				opacity          : 0;
				transform        : translateY(20px);
				transition       : 850ms ease-in-out;
				position         : fixed;
				width            : 100%;
				max-width        : 350px;
				visibility       : hidden;
				height           : 600px;
				max-height       : calc(100vh - 250px);
				overflow-y       : scroll;
				padding          : 0 20px;
				border           : 1px solid rgb(31 171 230);
				box-shadow       : 0px 0px 5px rgb(31 171 230);
				border-radius    : 5px;
				background-color : #fff;
				right            : 60px;
			}

			.kbs-beacon-content .kbs-beacon-articles-wrapper {
				height : 100%;
			}

			.kbs-beacon-content .kbs-beacon-search-wrapper {
				position    : sticky;
				bottom      : 0;
				width       : calc(100% + 40px);
				margin-left : -20px;
			}

			.kbs-beacon-content .kbs-beacon-search-wrapper input {
				box-shadow : 0px -2px 1px rgb(31 171 230);
				border     : 0px;
			}

			html body #kbs-beacon .kbs-beacon-content .kbs-beacon-search-wrapper input {
				outline : none;
			}

			.kbs-beacon-content input:focus {
				outline        : auto rgb(31 171 230);
				outline-offset : 0;
			}

			.kbs-beacon-content input[type="submit"] {
				background : rgb(31 171 230);
			}

			#kbs-beacon .kbs-beacon-content #kbs_ticket_wrap.form-submitted {
				display         : flex;
				height          : 100%;
				align-items     : center;
				justify-content : center;
			}

			#kbs-beacon .kbs-beacon-toggle__input[type=checkbox]:checked + .kbs-beacon-wrapper .kbs-beacon-icon {
				display : none;
			}

			#kbs-beacon .kbs-beacon-toggle__input[type=checkbox]:checked + .kbs-beacon-wrapper .kbs-beacon-close {
				display : inline-block;
			}

			#kbs-beacon .kbs-beacon-toggle__input[type=checkbox]:checked + .kbs-beacon-wrapper .kbs-beacon-content {
				opacity    : 1;
				transform  : translateY(0);
				visibility : visible;
			}

			/**
			** Form Styling
			 */
			#kbs-beacon .kbs-beacon-wrapper legend {
				display : none;
			}

			#kbs-beacon .kbs-beacon-wrapper fieldset {
				padding    : 0;
				border     : 0;
				text-align : left;
			}

			#kbs-beacon .kbs-beacon-wrapper fieldset input,
			#kbs-beacon .kbs-beacon-wrapper fieldset textarea,
			#kbs-beacon .kbs-beacon-wrapper fieldset button,
			#kbs-beacon input {
				width           : 100%;
				border-radius   : 5px;
				font-size       : 13px;
				text-decoration : none;
			}

			#kbs-beacon .kbs-beacon-content .kbs-beacon-header {
				position      : sticky;
				width         : calc(100% + 40px);
				margin-left   : -20px;
				top           : 0;
				background    : rgb(31 171 230);
				border-bottom : 1px solid rgb(31 171 230);
				box-shadow    : 0 0 5px rgb(31 171 230);
				align-items   : center;
				margin-bottom : 25px;
				color         : #fff;
			}

			#kbs-beacon .kbs-beacon-content .kbs-beacon-header img {
				max-height : 100%;
				max-width  : 100%;
				width      : auto;
				height     : auto;
				display    : block;
				position   : relative;
			}

			#kbs-beacon .kbs-beacon-content .kbs-beacon-header .kbs-beacon-header-navigation {
				display         : flex;
				width           : 100%;
				justify-content : space-around;
				align-items     : center;
				margin-top      : 15px;
				margin-bottom   : 10px;
			}

			#kbs-beacon .kbs-beacon-content .kbs-beacon-header .kbs-beacon-header-navigation span {
				display : block;
				padding : 5px 10px;
				cursor  : pointer;
			}

			#kbs-beacon .kbs-beacon-content .kbs-beacon-header .kbs-beacon-header-navigation span.active {
				background    : rgba(0, 0, 0, 0.2);
				border-radius : 9999px
			}

			#kbs-beacon .kbs-beacon-content::-webkit-scrollbar {
				width : 4px;
			}

			#kbs-beacon .kbs-beacon-content::-webkit-scrollbar-track {
				box-shadow    : inset 0 0 2px grey;
				border-radius : 2px;
			}

			#kbs-beacon .kbs-beacon-content::-webkit-scrollbar-thumb {
				border-radius : 2px;
				background    : rgb(31 171 230);
			}

			#kbs-beacon .kbs-beacon-content::-webkit-scrollbar-thumb:hover {
				background : black;
			}

			html body #kbs-beacon .hide {
				display : none;
			}

			/**
			** Firefox scrollbar style
			 */
			#kbs-beacon .kbs-beacon-content {
				scrollbar-width : thin; /* "auto" or "thin" */
				scrollbar-color : rgb(31 171 230) rgba(0, 0, 0, 0.2); /* scroll thumb and track */
			}

			/**
			** End form styling
			 */
			<?php

				if ( isset( $this->settings['floating_widget_position'] ) ){
					echo 'html body #kbs-beacon,html body #kbs-beacon .kbs-beacon-content{' . esc_attr( $this->settings['floating_widget_position'] ) . ' : 60px;}';
				}

				if ( isset( $this->settings['floating_widget_color'] ) && '' != $this->settings['floating_widget_color']  ){
					echo 'html body #kbs-beacon .kbs-beacon-wrapper{background-color:' . esc_attr( $this->settings['floating_widget_color'] ) . ';}';
					echo 'html body #kbs-beacon .kbs-beacon-content{ border-color: ' . esc_attr( $this->settings['floating_widget_color'] ) . '; box-shadow: 0px 0px 5px ' . esc_attr( $this->settings['floating_widget_color'] ) . '; }';
					echo 'html body #kbs-beacon .kbs-beacon-content::-webkit-scrollbar-thumb {background:' . esc_attr( $this->settings['floating_widget_color'] ) . ';}';
					echo 'html body #kbs-beacon .kbs-beacon-content { scrollbar-color : ' . esc_attr( $this->settings['floating_widget_color'] ) . ' rgba(0,0,0,0.2);}';
					echo 'html body #kbs-beacon .kbs-beacon-content .kbs-beacon-header{border-color:' . esc_attr( $this->settings['floating_widget_color'] ) . ' rgba(0,0,0,0.2); box-shadow: 0 0 5px ' . esc_attr( $this->settings['floating_widget_color'] ) . '; background:' . esc_attr( $this->settings['floating_widget_color'] ) . '; }';
					echo 'html body .kbs-beacon-content input:focus {outline-color:' . esc_attr( $this->settings['floating_widget_color'] ) . ';}';
					echo 'html body .kbs-beacon-content input[type="submit"] {background: ' . esc_attr( $this->settings['floating_widget_color'] ) . ';}';
					echo 'html body .kbs-beacon-content .kbs-beacon-search-wrapper input {box-shadow: 0px -2px 1px ' . esc_attr( $this->settings['floating_widget_color'] ) . ';}';
				}

				if ( isset( $this->settings['floating_widget_label'] ) && '1' == $this->settings['floating_widget_label'] ){
					echo 'html body #kbs-beacon .kbs-beacon-wrapper label {display:none;}';
				}
			?>
		</style> <!-- End style-->
		<?php
	}


	/**
	 * Our floating widget
	 *
	 * @since 1.6.0
	 */
	public function floating_widget(){

		$user  = wp_get_current_user();
		$roles = $user->roles;

		if ( is_user_logged_in() && in_array( 'support_agent', $roles ) ){
			return;
		}

		$widget_icon = 'fa-comment-o';

		if ( isset( $this->settings['floating_widget_icon'] ) && '' != $this->settings['floating_widget_icon'] ){
			$widget_icon = $this->settings['floating_widget_icon'];
		}

		$logo_image = KBS_PLUGIN_URL . 'assets/images/icon-128x128.png';
		if ( isset( $this->settings['floating_widget_logo'] ) && '' != $this->settings['floating_widget_logo'] ){
			$logo_image = $this->settings['floating_widget_logo'];
		}
		$this->widget_css();
		?>

		<?php
		$html = '<div id="kbs-beacon">';
		$html .= '<input type="checkbox" class="kbs-beacon-toggle__input">';
		$html .= '<div class="kbs-beacon-wrapper">';
		$html .= '<span class="kbs-beacon-icon fa ' . esc_attr( $widget_icon ) . '"></span>';
		$html .= '<span class="kbs-beacon-close fa fa-close"></span>';
		$html .= '<div class="kbs-beacon-content">';
		$html .= '<div class="kbs-beacon-header">';
		$html .= '<img src="' . esc_url( $logo_image ) . '">';
		$html .= '<div class="kbs-beacon-header-navigation">';
		$html .= '<span class="fa fa-search active" id="kbs-beacon-search"> ' . esc_html__( 'Search Articles', 'kb-support' ) . '</span>';
		$html .= '<span class="fa fa-commenting " id="kbs-beacon-ask"> ' . esc_html__( 'Submit ticket', 'kb-support' ) . '</span>';
		$html .= '</div>'; // .kbs-beacon-header-navigation
		$html .= '</div>'; // .kbs-beacon-header
		$html .= '<div class="kbs-beacon-articles-wrapper" data-toggle="kbs-beacon-search">';
		$html .= '</div>'; // kbs-beacon-articles-wrapper
		$html .= '<div class="kbs-beacon-search-wrapper">';
		$html .= '<input type="text" id="kbs-beacon-search-input" placeholder="' . esc_html__( 'Search articles here', 'kb-support' ) . '">';
		$html .= '</div>'; // .kbs-beacon-search-wrapper
		$html .= '<div class="kbs-beacon-form-wrapper hide"  data-toggle="kbs-beacon-ask">';
		$html .= do_shortcode( '[kbs_submit form="' . absint( $this->settings['floating_widget_form'] ) . '"]' );
		$html .= '</div>'; // kbs-beacon-form-wrapper
		$html .= '</div>'; // .kbs-beacon-content
		$html .= '</div>'; // .kbs-beacon-wrapper
		$html .= '</div>'; // #kbs-beacon

		echo $html;
	}

	public function kb_articles(){

	}

}

KBS_Floating_Widget::get_instance();
