<?php

/**
 * Plugin Name: ImagePilot Pro
 * Plugin URI: mailto:prasadkirpekar96@gmail.com
 * Description: Easy to use plugin to compress images from WordPress dashboard
 * Author: Prasad Kirpekar
 * Author URI: mailto:prasadkirpekar96@gmail.com
 * Version: 0.1.2
 */
define( 'IMGPLT_URL', plugin_dir_url( __FILE__ ) );
define( 'IMGPLT_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMGPLT_VERSION', '0.1.2' );
//define('IMGPLT_PRODUCTION', 'yes');
//define('IMGPLT_DEVELOPMENT', 'yes');

if ( !function_exists( 'imagepilot_fs' ) ) {
    // Create a helper function for easy SDK access.
    function imagepilot_fs()
    {
        global  $imagepilot_fs ;
        
        if ( !isset( $imagepilot_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $imagepilot_fs = fs_dynamic_init( array(
                'id'             => '12136',
                'slug'           => 'imagepilot',
                'premium_slug'   => 'imagepilot-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_7dfd0f575f2beddfdf6361ae7e13a',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'    => 'imagepilot',
                'support' => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $imagepilot_fs;
    }
    
    // Init Freemius.
    imagepilot_fs();
    // Signal that SDK was initiated.
    do_action( 'imagepilot_fs_loaded' );
}

class IMGPLTMain
{
    public function boot()
    {
        $this->loadClasses();
        $this->registerShortCodes();
        $this->ActivatePlugin();
        $this->renderMenu();
        $this->registerHooks();
        $this->registerAjax();
    }
    
    public function registerHooks()
    {
        $wpsqz = new \IMGPLTMain\Classes\ImagePilot();
        add_filter(
            'script_loader_tag',
            array( $this, 'addModuleToScript' ),
            10,
            3
        );
        $options = $wpsqz->getOptions();
        if ( $options['preventWPScaleDown'] ) {
            add_filter( 'big_image_size_threshold', '__return_false' );
        }
    }
    
    public function registerAjax()
    {
        $wpsqz = new \IMGPLTMain\Classes\ImagePilot();
        add_action( 'wp_ajax_list_media', array( $wpsqz, 'listMedia' ) );
        add_action( 'wp_ajax_list_files', array( $wpsqz, 'listFiles' ) );
        add_action( 'wp_ajax_save_image', array( $wpsqz, 'saveImage' ) );
        add_action( 'wp_ajax_save_file', array( $wpsqz, 'saveFile' ) );
        add_action( 'wp_ajax_get_settings', array( $wpsqz, 'getSettings' ) );
        add_action( 'wp_ajax_update_settings', array( $wpsqz, 'updateSettings' ) );
    }
    
    public function loadClasses()
    {
        require IMGPLT_DIR . 'includes/autoload.php';
    }
    
    public function renderMenu()
    {
        add_action( 'admin_menu', function () {
            if ( !current_user_can( 'manage_options' ) ) {
                return;
            }
            global  $submenu ;
            add_menu_page(
                'ImagePilot',
                'ImagePilot',
                'manage_options',
                'imagepilot',
                array( $this, 'renderAdminPage' ),
                'dashicons-editor-contract',
                75.09999999999999
            );
        } );
    }
    
    public function addModuleToScript( $tag, $handle, $src )
    {
        if ( $handle === 'IMGPLT-script-boot' ) {
            $tag = '<script type="module" id="IMGPLT-script-boot" src="' . esc_url( $src ) . '"></script>';
        }
        return $tag;
    }
    
    public function renderAdminPage()
    {
        $loadAssets = new \IMGPLTMain\Classes\LoadAssets();
        $loadAssets->enqueueAssets();
        $IMGPLT = apply_filters( 'IMGPLT/admin_app_vars', array(
            'assets_url'     => IMGPLT_URL . 'assets/',
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'is_pro'         => !imagepilot_fs()->is_not_paying(),
            'upgrade_url'    => imagepilot_fs()->get_upgrade_url(),
            'account_url'    => imagepilot_fs()->get_account_url(),
            'plugin_version' => IMGPLT_VERSION,
        ) );
        wp_localize_script( 'IMGPLT-script-boot', 'IMGPLTAdmin', $IMGPLT );
        echo  '<div class="IMGPLT-admin-page" id="WPWVT_app">
            
            <router-view></router-view>
        </div>' ;
    }
    
    public function registerShortCodes()
    {
        // your shortcode here
    }
    
    public function ActivatePlugin()
    {
    }

}
( new IMGPLTMain() )->boot();