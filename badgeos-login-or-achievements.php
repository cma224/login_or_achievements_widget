<?php
/**
 * Plugin Name: Login Or Achievements Widget for BadgeOS
 * Description: This BadgeOS add-on adds a widget to display earned achievements for logged in users and a login box for logged out users.
 * Version: 1.0.0
 * License: GNU AGPL
 * Author: Cristian Abello
 * Author URI: mailto:cristian.abello@valpo.edu
 * Text Domain: badgeos-login-or-achievements
 */



/**
 * Our main plugin instantiation class
 *
 * @since 1.0.0
 */
class BadgeOS_Login_Or_Achievements {

	/**
	 * Get everything running.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );
	
		// Load translations
		load_plugin_textdomain( 'badgeos-login-or-achievements', false, dirname( $this->basename ) . '/languages' );
	
		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	
		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
	
	     // use widgets_init action hook to execute custom function
        add_action( 'widgets_init', array( $this, 'badgeos_register_login_or_achievement_widget' ) );

		// Hook in our dependent files and methods
		add_action( 'init', array( $this, 'includes' ), 0 );

        // Load custom js and css
        add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_and_styles' ), 1 );
        
	}


	/**
	 * Include our file dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/login-or-achievement-widget.php' );
			require_once( $this->directory_path . '/includes/functions.php' );
		}

	}


    /**
     * Register our widget
     *
     * @since 1.0.0
     */
    public function badgeos_register_login_or_achievement_widget() {

        // Registering widget
        if ( class_exists('BadgeOS') && class_exists('login_or_achievements_widget'))
            register_widget( 'login_or_achievements_widget' );
    }

	/**
	 * Register our included scripts and stles
	 *
	 * @since 1.0.0
	 */
	public function load_scripts_and_styles() {

        // Load css file
        wp_enqueue_style( 'badgeos-login-or-achievements', $this->directory_url . '/css/style.css', null, '1.0.0' );

        // Load script
        wp_enqueue_script( 'badgeos-login-or-achievements', $this->directory_url . '/js/login-or-achievements.js', array() );
	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
        // Do some activation things.
	}

	/**
	 * Deactivation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		// Do some deactivation things. Note: this plugin may
		// auto-deactivate due to $this->maybe_disable_plugin()

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {
		
		if (class_exists('BadgeOS')){
			return true;
		}
		else{
			return false;
	}
	}

	/**
	 * Potentially output a custom error message and deactivate
	 * this plugin, if we don't meet requriements.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			echo '<div id="message" class="error">';
			
            if ( !class_exists( 'BadgeOS' ))
            {
			echo '<p>' . sprintf(__('So you want to break WordPress, huh? BadgeOS Login Or Achievements Widget Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-addon'), admin_url('plugins.php')) . '</p>';
            }
            echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
			
			if ( isset( $_GET['activate'] ) ) 
            unset( $_GET['activate'] );
        
		}

	}

}

// Instantiate our class to a global variable that we can access elsewhere
$GLOBALS['badgeos_login_or_achievements_addon'] = new BadgeOS_Login_Or_Achievements();
