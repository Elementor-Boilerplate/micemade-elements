<?php
/**
 * Plugin Name: Micemade Elements
 * Description: Custom elements for Elementor, created by Micemade
 * Plugin URI: https://github.com/Micemade/micemade-elements/
 * Version: 0.0.2
 * Author: micemade
 * Author URI: http://micemade.com
 * Text Domain: micemade-elements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Elements {


	private static $instance = null;
	
	public static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public function init() {
		
		if( self::$instance->Elementor_plugin_activation_check() ) {
			
			define('MICEMADE_ELEMENTS_DIR', plugin_dir_path( __FILE__ ) );
			define('MICEMADE_ELEMENTS_URL', plugin_dir_url( __FILE__ ) );
			
			
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
			//add_action( 'elementor/init', array( $this, 'widgets_registered' ) );
			
			add_action('plugins_loaded', array( self::$instance, 'load_plugin_textdomain') );
			
			self::$instance->activation_checks();
			
			self::$instance->includes();
			
			// Enqueue script and styles for ADMIN
			//add_action( 'admin_enqueue_scripts', array( self::$instance, 'micemade_elements_admin_js_css' ) );
			
			// Enqueue scripts and styles for frontend
			add_action( 'wp_enqueue_scripts', array( self::$instance,'micemade_elements_styles') );
			//add_action( 'wp_enqueue_scripts', array( self::$instance,'micemade_elements_scripts') );
		
		}else {
			
			add_action( 'admin_notices', array( self::$instance ,'admin_notice') ); 
		}
	
		
	}
	

	private function Elementor_plugin_activation_check() {
		
		$micemade_elements_is_active = false;
		
		//if ( defined( 'ELEMENTOR_PATH' ) && class_exists( 'Elementor\Widget_Base' ) ) {
		if ( in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			
			$micemade_elements_is_active = true; 
			define('ELEMENTOR_IS_ACTIVE', true );						
		}else{
			define('ELEMENTOR_IS_ACTIVE', false );	
		}
		
		return $micemade_elements_is_active;
		
	}
	
	
	private function activation_checks() {
		
		// VARIOUS PLUGINS ACTIVATION CHECK:
		// if WOOCOMMERCE activated:
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )  {
			define('MICEMADE_ELEMENTS_WOO_ACTIVE',true );								
		}else{
			define('MICEMADE_ELEMENTS_WOO_ACTIVE',false );	
		}
		// if YITH WC WISHLIST activated:
		if ( in_array( 'yith-woocommerce-wishlist/init.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )  {
			define('MICEMADE_ELEMENTS_WISHLIST_ACTIVE',true );
		}else{
			define('MICEMADE_ELEMENTS_WISHLIST_ACTIVE',false );
		}
		
		// if WPML activated:
		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )  {
			define('MICEMADE_ELEMENTS_WPML_ON',true );										
		}else{
			define('MICEMADE_ELEMENTS_WPML_ON',false );	
		}
		

	}
	
	public function widgets_registered() {

		// get our own widgets up and running:
		// copied from widgets-manager.php
		if ( class_exists( 'Elementor\Plugin' ) ) {
			
			if ( is_callable( 'Elementor\Plugin', 'instance' ) ) {
								
				$theElementor = Elementor\Plugin::instance();
				
				if ( isset( $theElementor->widgets_manager ) ) {
					
					if ( method_exists( $theElementor->widgets_manager, 'register_widget_type' ) ) {

						$widgets = self::$instance -> widgets_list();
						
						foreach( $widgets as $file => $class ) {
							$widget_file   = 'plugins/elementor/'.$file.'.php';
							$template_file = locate_template( $widget_file );
													
							if ( !$template_file || !is_readable( $template_file ) ) {
								$template_file = plugin_dir_path(__FILE__) . 'widgets/'.$file.'.php';
							}
							
							if ( $template_file && is_readable( $template_file ) ) {
								require_once $template_file;
								
								$widget_class = 'Elementor\\' . $class;
								
								$theElementor->widgets_manager->register_widget_type( new $widget_class );
							}
							
						} // end foreach
						
					}
					
				}
				
			}
			
		}
		
	}
	
	public function widgets_list() {
		
		$widgets_list = array(
			'micemade-wc-products'			=> 'Micemade_WC_Products',
			'micemade-wc-single-product'	=> 'Micemade_WC_Single_Product',
			'micemade-posts-grid'			=> 'Micemade_Posts_Grid',
		);
		
		return $widgets_list;
		
	}
	
	public function includes () {
		
		$plugin_path = plugin_dir_path( __FILE__ );
		include( $plugin_path . "/includes/Parsedown.php" );
		include( $plugin_path . "/includes/admin.php" );
		include( $plugin_path . "/includes/helpers.php" );
		include( $plugin_path . "/includes/wc-functions.php" );
		
	}
	
	
	/**
	 * Load Plugin Text Domain
	 *
	 * Looks for the plugin translation files in certain directories and loads
	 * them to allow the plugin to be localised
	 */
	public function load_plugin_textdomain() {
					
		$lang_dir = apply_filters('micemade_elements_lang_dir', trailingslashit( MICEMADE_ELEMENTS_DIR . 'languages') );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters('plugin_locale', get_locale(), 'micemade-elements');
		$mofile = sprintf('%1$s-%2$s.mo', 'micemade-elements', $locale);

		// Setup paths to current locale file
		$mofile_local = $lang_dir . $mofile;

		if (file_exists($mofile_local)) {
			// Look in the /wp-content/plugins/micemade-elements/languages/ folder
			load_textdomain('micemade-elements', $mofile_local);
		}
		else {
			// Load the default language files
			load_plugin_textdomain('micemade-elements', false, $lang_dir);
		}

		return false;
	}
	
	// ENQUEUE STYLES
	public function micemade_elements_styles () {
		
		// CSS styles:
		wp_register_style( 'micemade-elements', MICEMADE_ELEMENTS_URL . 'assets/css/micemade-elements.css' );
		wp_enqueue_style( 'micemade-elements' );
		
		
	}
	
	// ENQUEUE SCRIPTS
	public function micemade_elements_scripts () {
		
		// JS scripts:
		wp_register_script('micemade-elements-js', MICEMADE_ELEMENTS_URL .'assets/js/scripts.min.js');
		wp_enqueue_script('micemade-elements-js', MICEMADE_ELEMENTS_URL .'assets/js/scripts.min.js', array('jQuery'), '1.0', true);
		
		
		// Localize the script with our data.
		$translation_array = array( 
			'loading_qb' => __( 'Loading quick view','micemade_elements' )
		);
		wp_localize_script( 'vc-ase-ajax-js', 'wplocalize_vcase_js', $translation_array );
	}
	
	public function ajax_url_var() {
		echo '<script type="text/javascript">var micemade_elements_ajaxurl = "'. admin_url("admin-ajax.php") .'"</script>';
	}
	
	public function admin_notice() {
		
		$class = "error updated settings-error notice is-dismissible";
		$message = __("Micemade elements is not effective without \"Elementor\" plugin activated. Either install and activate  \"Elementor\" plugin or deactivate Micemade elements. ","micemade-elements");
        echo"<div class=\"$class\"> <p>$message</p></div>"; 
		
	}
	
	function updater() {
			
		require_once( plugin_dir_path( __FILE__ ) . 'github_updater.php' );
		if ( is_admin() ) {
			new Micemade_GitHub_Plugin_Updater( __FILE__, 'Micemade', "micemade-elements" );
		}
	}

	
} // end class MM_Elements

MM_Elements::get_instance()->init();