<?php


/*
*
* making wp plugin admin page and functionalities
*
*/



class PostsStatsSettings {
    
    /**
     * Start up
     */
    public function __construct() {
    	add_action('admin_enqueue_scripts', array($this, 'add_styles_and_scripts'));
        add_action('admin_menu', array($this,'add_plugin_page'));
        add_action('admin_init', array($this,'page_init'));
        add_action('init',array($this, 'register_session'));
        add_action('admin_notices', array($this, 'ps_admin_notice'));
    }

    /**
    /* Add css for admin page
    **/
    public function add_styles_and_scripts() {
    	wp_enqueue_style('cps_admin_css', plugins_url() . '/posts-stats/includes/assets/css/cps-admin.css');
        wp_enqueue_script('config_check', plugins_url() . '/posts-stats/includes/assets/js/config_check.js');

        $translation_array = array('templateUrl' => plugins_url());
        wp_localize_script('config_check', 'theme', $translation_array);
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // Create new top-level menu
        $page_title = 'Posts Stats Settings Page';
        $menu_title = 'Post Stats';
        $capability = 'manage_options';
        $slug = 'ps_settings_page';
        $callback = array($this, 'create_admin_page');
        $icon = 'dashicons-chart-pie';
        // $postion = 100;

        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon);
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set errors
        settings_errors();
        ?>
        <div class="wrap">
            <h1>Posts Stats Settings</h1>
            <form method="post" action="<?php echo esc_url(plugins_url('posts-stats/register.php')); ?>">
            <?php
                // This prints out all hidden setting fields
                settings_fields('ps_settings_page');
                do_settings_sections('ps_settings_page');
                submit_button(__('Save Changes'), 'primary', 'submit', false);
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {

        // register_setting(
        //     'ps_settings_page', // Option group
        //     'wp_site_name', // Option name
        //     array( $this, 'ps_validate_inputs' ) // Validate inputs
        // );
        // register_setting( 'ps_option_group', 'ps_options', array($this, 'ps_auth_validate'));

        // NAME YOUR WP SITE input
        add_settings_section('ps_section_site_name', 'Send name of this site', array($this, 'print_section_info'), 'ps_settings_page');
        add_settings_field('ps_field_site_name', 'Site Name', array($this, 'print_fields'), 'ps_settings_page', 'ps_section_site_name', array('id' => 'ps_field_site_name'));

        // WHERE TO URL input
        add_settings_section('ps_section_web_app_url', 'Input "where to" home url', array($this, 'print_section_info'), 'ps_settings_page');
        add_settings_field('ps_field_web_app_url', 'URL', array($this, 'print_fields'), 'ps_settings_page', 'ps_section_web_app_url', array('id' => 'ps_field_web_app_url'));

        // AUTHENTICATION inputs
        add_settings_section('ps_section_aut_credentials', 'Input authentication credentials', array($this, 'print_section_info'), 'ps_settings_page');
        add_settings_field('ps_field_aut_credentials', 'Credentials', array($this, 'print_fields'), 'ps_settings_page', 'ps_section_aut_credentials', array('id' => 'ps_field_aut_credentials'));

        // CATEGORIES checkboxes
        add_settings_section('ps_section_categories', 'Categories to check', array( $this, 'print_section_info'), 'ps_settings_page');
        add_settings_field('ps_field_categories', '', array($this, 'print_fields'), 'ps_settings_page', 'ps_section_categories', array('id' => 'ps_field_categories'));
    }

    /** 
     * Print the Section text
     */
    public function print_section_info($arg) {

        switch ($arg['id']) {
            case 'ps_section_site_name':
                print 'Select your categories below:';
                break;
            
            case 'ps_section_web_app_url':
                print 'Enter web app url below:';
                break;

            case 'ps_section_aut_credentials':
                print 'Enter this site name below to send to web app:';
                break;

            case 'ps_section_categories':
                print 'Enter authentication credentials for external web app:';
                break;

            default:
                print 'No description.';
                break;
        }
    }

    /** 
     * Print the settings fields
     */
    public function print_fields($arg) {

        switch ($arg['id']) {
            case 'ps_field_site_name':
                printf("<p><input id='wp_site_name' type='text' name='wp_site_name'/></p>");
                break;
            
            case 'ps_field_web_app_url':
                printf("<p><input id='url' type='text' name='url'/></p>");
                break;

            case 'ps_field_aut_credentials':
                printf("<p><input id='auth_username' type='text' name='auth_username' placeholder='Username'/></p>
                        <p><input id='auth_password' type='password' name='auth_password' placeholder='Password'/></p>");
                break;

            case 'ps_field_categories':
                $cats = $this->get_all_cats();
                ?>
                <div class="cps-categories-wrapper" columns="auto 3">
                    <?php
                    foreach ($cats as $key => $cat) {
                        printf(
                            "<p><input id='$key' type='checkbox' class='categories' name='categories[$cat->slug]' value='$cat->name' /><label for='$key'>$cat->name</label>&nbsp;&nbsp;&nbsp;</p>"
                        );
                    }
                    ?>
                </div>
                <?php
                break;

            
            default:
                print 'No callback function.';
                break;
        }
    }

    public function get_all_cats() {

    	return get_categories(['hide_empty' => FALSE]);
    }

    // failed authentication error notice
    public function ps_admin_notice() {


        global $pagenow;

        if (isset($_SESSION['status']) && isset($_GET['page']) && $_GET['page'] == 'ps_settings_page') {

            $status = $_SESSION['status'];
            unset($_SESSION['status']);

            switch ($status) {
                case 'auth_failed':
                    $type = 'notice-error';
                    $message = __( 'Authorization unsuccessful, wrong user or pass!!', 'ps_settings_page' );
                    add_settings_error(
                        'ps_option_group',
                        esc_attr( 'ps_error_auth_failed' ),
                        $message,
                        $type
                    );
                    break;

                case 'registration_success':
                    $type = 'notice-success';
                    $message = __( 'Registration successful.', 'ps_settings_page' );
                    add_settings_error(
                        'ps_option_group',
                        esc_attr( 'ps_success_registration_successful' ),
                        $message,
                        $type
                    );
                    break;

                case 'update_success':
                    $type = 'notice-success';
                    $message = __( 'Update successful.', 'ps_settings_page' );
                    add_settings_error(
                        'ps_option_group',
                        esc_attr( 'ps_notice_update_successful' ),
                        $message,
                        $type
                    );
                    break;

                case 'no_change':
                    $type = 'notice-warning';
                    $message = __( 'Nothing to update, all data still unchanged.', 'ps_settings_page' );
                    add_settings_error(
                        'ps_option_group',
                        esc_attr( 'ps_notice_no_change' ),
                        $message,
                        $type
                    );
                    break;
                
                default:
                    var_dump($_SESSION['chasing_bug']);
                    var_dump($_SESSION['status']);
                    $type = 'error';
                    $message = __( 'Strange how you came to this.', 'ps_settings_page' );
                    add_settings_error(
                        'ps_option_group',
                        esc_attr( 'ps_strange_thing' ),
                        $message,
                        $type
                    );
                    break;
            }

        }
    }

    public function register_session(){
        if( !session_id() )
            session_start();
    }
}

if( is_admin() )
    $posts_stats_settings_page = new PostsStatsSettings();