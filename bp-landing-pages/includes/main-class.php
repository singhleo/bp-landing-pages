<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widgets_Plugin' ) ):

    class BPFP_Widgets_Plugin {

        /**
         * Main includes
         * @var array
         */
        private $main_includes = array (
            //core
            'templates',
            'class-bpfp-widget-helper',
            //widgets
            'widgets/abstract-bpfp-widget',
            'widgets/class-bpfp-widget-content',
            'widgets/class-bpfp-widget-richcontent',
            'widgets/class-bpfp-widget-twitterfeed',
            'widgets/class-bpfp-widget-fbpage',
        );

        /**
         * Admin includes
         * @var array
         */
        private $admin_includes = array (
            'admin',
        );

        /**
         * Default options for the plugin.
         * After the user saves options the first time they are loaded from the DB.
         *
         * @var array
         */
        private $default_options = array (
            'enabled_for' => array ( 'members' ),
            'mobile_maxwidth' => '800',
            'widgets_allowed' => array ( array ( 'members' => 'richcontent' ), array ( 'groups' => 'richcontent' ) ),
        );
        public $options = array ();
        public $network_activated = false;
        private
        /**
         * Name of the subnav item
         */
            $_subnav_name = '',
            /**
             * slug of subnav item
             */
            $_subnav_slug = 'front-page',
            /**
             * @type BPFP_Widget_Helper
             */
            $_factory = false,
            /**
             * @type BPFP_Widgets_Admin
             */
            $_admin = false;

        /**
         * Returns the Singletion object
         * @staticvar BPFP_Widgets_Plugin $instance
         * @return BPFP_Widgets_Plugin
         */
        public static function instance () {
            // Store the instance locally to avoid private static replication
            static $instance = null;

            // Only run these methods if they haven't been run previously
            if ( null === $instance ) {
                $instance = new BPFP_Widgets_Plugin;
                $instance->_subnav_name = __( 'Front Page', 'bp-landing-pages' );
                $instance->setup_globals();
                $instance->setup_actions();
                $instance->setup_textdomain();
            }

            // Always return the instance
            return $instance;
        }

        private function __construct () {
            //nothing
        }

        private function setup_globals () {
            $this->network_activated = $this->is_network_activated();

            // DEFAULT CONFIGURATION OPTIONS
            $default_options = $this->default_options;

            $saved_options = $this->network_activated ? get_site_option( 'bpfp_widgets_options' ) : get_option( 'bpfp_widgets_options' );
            $saved_options = maybe_unserialize( $saved_options );

            $this->options = wp_parse_args( $saved_options, $default_options );
        }

        /**
         * Check if the plugin is activated network wide(in multisite)
         * 
         * @return boolean
         */
        private function is_network_activated () {
            $network_activated = false;
            if ( is_multisite() ) {
                if ( !function_exists( 'is_plugin_active_for_network' ) )
                    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

                if ( is_plugin_active_for_network( 'bp-fp-widgets/loader.php' ) ) {
                    $network_activated = true;
                }
            }
            return $network_activated;
        }

        private function setup_actions () {
            // Hook into BuddyPress init
            add_action( 'bp_init', array ( $this, 'bp_init' ) );

            add_action( 'bp_loaded', array ( $this, 'bp_loaded' ) );

            add_action( 'bp_setup_nav', array ( $this, 'bp_setup_nav' ) );
        }

        public function setup_textdomain () {
            $domain = 'bp-landing-pages';
            $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

            //first try to load from wp-content/languages/plugins/ directory
            load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo' );

            //if not found, then load from bp-fp-widgets/languages directory
            load_plugin_textdomain( $domain, false, 'bp-landing-pages/languages' );
        }

        public function bp_loaded () {
            if ( function_exists( 'bp_register_template_stack' ) ) {
                //add new location in template stack
                //13 is between theme and buddypress's template directory
                bp_register_template_stack( array ( $this, 'register_template_stack' ), 13 );

                add_filter( 'bp_get_template_stack', array ( $this, 'maybe_remove_template_stack' ) );
            }

            if ( bp_is_active( 'groups' ) ) {
                $enabled_for = $this->option( 'enabled_for' );
                if ( !empty( $enabled_for ) && in_array( 'groups', $enabled_for ) ) {
                    include_once BPFPWIDGETS_PLUGIN_DIR . 'includes/group-extension.php';
                }
            }

            add_action( 'bp_template_content', array ( $this, 'output_frontpage_content' ) );
        }

        public function bp_init () {
            // Admin
            if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ) {
                $this->load_admin();
            }

            $this->load_main();
        }

        private function load_admin () {
            $this->do_includes( $this->admin_includes );
            $this->_admin = BPFP_Widgets_Admin::instance();
        }

        private function load_main () {
            $this->do_includes( $this->main_includes );
            $this->_factory = BPFP_Widget_Helper::instance();

            // Front End Assets
            if ( !is_admin() && !is_network_admin() ) {
                add_action( 'wp_enqueue_scripts', array ( $this, 'assets' ) );
            }
        }

        /**
         * Get plugin's admin class instance.
         * 
         * @return BPFP_Widgets_Admin
         */
        public function admin () {
            return $this->_admin;
        }

        /**
         * Get widget factory class instance.
         * 
         * @return BPFP_Widget_Helper
         */
        public function factory () {
            return $this->_factory;
        }

        public function bp_setup_nav () {
            $add_nav = false;
            $enabled_for = $this->option( 'enabled_for' );
            if ( !empty( $enabled_for ) && in_array( 'members', $enabled_for ) ) {
                $add_nav = true;
            }

            if ( !$add_nav )
                return;

            // Get the settings slug.
            $settings_slug = bp_get_settings_slug();

            bp_core_new_subnav_item( array (
                'name' => $this->_subnav_name,
                'slug' => $this->_subnav_slug,
                'parent_url' => trailingslashit( bp_displayed_user_domain() . $settings_slug ),
                'parent_slug' => $settings_slug,
                'screen_function' => array ( $this, 'screen_edit_widgets' ),
                'position' => 29,
                'user_has_access' => bp_core_can_edit_settings()
                ), 'members' );

            return false;
        }

        public function screen_edit_widgets () {
            if ( bp_is_user() ) {
                add_action( 'bp_template_title', array ( $this, 'members_edit_widgets_title' ) );
                add_action( 'bp_template_content', array ( $this, 'members_edit_widgets_contents' ) );
                bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                
            }
        }

        public function members_edit_widgets_title () {
            echo apply_filters( 'bpfp_member_edit_widgets_title', __( 'Manage your page', 'bp-landing-pages' ) );
        }

        public function members_edit_widgets_contents () {
            bpfp_widget_load_template( 'members/manage' );
        }

        public function output_frontpage_content () {
            $has_custom_frontpage = false;
            $object_type = 'members';

            if ( bp_is_user() && 'front' == bp_current_component() ) {
                //Does the current user want to have a custom front page template?
                $has_custom_frontpage = get_user_meta( bp_displayed_user_id(), '_has_custom_frontpage', true );
            } else if ( bp_is_active( 'groups' ) && bp_is_group() && 'home' == bp_current_action() ) {
                $group = groups_get_current_group();
                if ( empty( $group ) || empty( $group->id ) )
                    return $stack;

                //Does the current group want to have a custom front page template?
                $has_custom_frontpage = groups_get_groupmeta( $group->id, '_has_custom_frontpage', true );
                $object_type = 'groups';
            }

            if ( $has_custom_frontpage ) {
                bpfp_widget_load_template( $object_type . '/output' );
            }
        }

        public function assets () {
            $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

            //assets for edit-widgets screen
            if ( ( bp_is_user() && 'settings' == bp_current_component() && $this->_subnav_slug == bp_current_action() ) ||
                ( bp_is_active( 'groups' ) && bp_is_group() && 'admin' == bp_current_action() && $this->_subnav_slug == bp_action_variable( 0 ) )
            ) {
                wp_enqueue_script( 'jquery-ui-sortable' );

                /**
                 * Should we load our visual editor library?
                 */
                $is_available = $this->factory()->is_widget_available_for( 'richcontent' );
                if ( $is_available ) {
                    wp_enqueue_script( 'trumbowyg', BPFPWIDGETS_PLUGIN_URL . 'assets/trumbowyg/dist/trumbowyg' . $min . '.js', array ( 'jquery' ), '2.1.0', true );
                    wp_enqueue_style( 'trumbowyg', BPFPWIDGETS_PLUGIN_URL . 'assets/trumbowyg/dist/ui/trumbowyg' . $min . '.css', '2.1.0', true );
                }

                wp_enqueue_script( 'bpfp_widgets', BPFPWIDGETS_PLUGIN_URL . 'assets/script' . $min . '.js', array ( 'jquery', 'jquery-form' ), '0.1', true );

                $data = apply_filters( 'bpfp_widgets_script_data', array (
                    'config' => array (
                        'nonce' => array (
                            'init' => wp_create_nonce( 'bpfp_widget_init' ),
                            'delete' => wp_create_nonce( 'bpfp_widget_delete' ),
                            'change_status' => wp_create_nonce( 'bpfp_change_status' ),
                            'reorder' => wp_create_nonce( 'bpfp_widgets_reorder' ),
                            'init_group' => wp_create_nonce( 'bpfp_widgets_init_groups_ui' ),
                        ),
                        'action' => array (
                            'init_group' => 'bpfp_widgets_init_groups_ui',
                        )
                    ),
                    'translations' => array (
                        'confirm_delete' => __( 'Are you sure you want to delete it?', 'bp-landing-pages' ),
                    ),
                    ) );
                wp_localize_script( 'bpfp_widgets', 'BPFP_WIDGETS', $data );

                wp_enqueue_style( 'bpfp_widgets', BPFPWIDGETS_PLUGIN_URL . 'assets/style' . $min . '.css', array (), '0.1' );
            }

            //assets for view(front page) screen
            if ( ( bp_is_user() && 'front' == bp_current_component() ) ||
                ( bp_is_active( 'groups' ) && bp_is_group() && 'home' == bp_current_action() )
            ) {
                wp_enqueue_style( 'bpfp_widgets', BPFPWIDGETS_PLUGIN_URL . 'assets/style' . $min . '.css', array (), '0.1' );
            }
        }

        public function do_includes ( $includes = array () ) {
            foreach ( ( array ) $includes as $include ) {
                require_once( BPFPWIDGETS_PLUGIN_DIR . 'includes/' . $include . '.php' );
            }
        }

        public function register_template_stack () {
            return BPFPWIDGETS_PLUGIN_DIR . 'templates';
        }

        function maybe_remove_template_stack ( $stack ) {
            $need_template_stack = false;
            $enabled_for = $this->option( 'enabled_for' );

            if ( bp_is_user() && !empty( $enabled_for ) && in_array( 'members', $enabled_for ) ) {
                //Does the current user want to have a custom front page template?
                $need_template_stack = get_user_meta( bp_displayed_user_id(), '_has_custom_frontpage', true );
            } else if ( bp_is_active( 'groups' ) && bp_is_group() && !empty( $enabled_for ) && in_array( 'groups', $enabled_for ) ) {
                $group = groups_get_current_group();
                if ( empty( $group ) || empty( $group->id ) )
                    return $stack;

                //Does the current group want to have a custom front page template?
                $need_template_stack = groups_get_groupmeta( $group->id, '_has_custom_frontpage', true );
            }

            if ( !$need_template_stack ) {
                //remove this plugin's template stack
                $new_stack = array ();
                foreach ( $stack as $filepath ) {
                    if ( strpos( $filepath, BPFPWIDGETS_PLUGIN_DIR ) === false ) {
                        $new_stack[] = $filepath;
                    }
                }

                return $new_stack;
            }

            return $stack;
        }

        public function option ( $key ) {
            $key = strtolower( $key );
            $option = isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;

            //@todo: add a filter for option before returning.
            return $option;
        }

    }

    

    

    

// End class BPFP_Widgets_Plugin

endif;