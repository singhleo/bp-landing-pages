<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widgets_Admin' ) ):

    class BPFP_Widgets_Admin {

        /**
         * Plugin options
         *
         * @var array
         */
        public $options = array ();
        private $network_activated = false,
            $plugin_slug = 'bpfp_widgets',
            $menu_hook = 'admin_menu',
            $settings_page = 'options-general.php',
            $capability = 'manage_options',
            $form_action = 'options.php',
            $option_name = 'bpfp_widgets_options',
            $plugin_settings_url;

        /**
         * Empty constructor function to ensure a single instance
         */
        public function __construct () {
            // ... leave empty, see Singleton below
        }

        /**
         * 
         * @staticvar type $instance
         * @return BPFP_Widgets_Admin
         */
        public static function instance () {
            static $instance = null;

            if ( null === $instance ) {
                $instance = new BPFP_Widgets_Admin;
                $instance->setup();
            }

            return $instance;
        }

        public function option ( $key ) {
            $value = bpfp_widgets()->option( $key );
            return $value;
        }

        public function setup () {
            if ( (!is_admin() && !is_network_admin() ) || !current_user_can( 'manage_options' ) ) {
                return;
            }

            $this->plugin_settings_url = admin_url( 'options-general.php?page=' . $this->plugin_slug );

            $this->network_activated = $this->is_network_activated();

            //if the plugin is activated network wide in multisite, we need to override few variables
            if ( $this->network_activated ) {
                // Main settings page - menu hook
                $this->menu_hook = 'network_admin_menu';

                // Main settings page - parent page
                $this->settings_page = 'settings.php';

                // Main settings page - Capability
                $this->capability = 'manage_network_options';

                // Settins page - form's action attribute
                $this->form_action = 'edit.php?action=' . $this->plugin_slug;

                // Plugin settings page url
                $this->plugin_settings_url = network_admin_url( 'settings.php?page=' . $this->plugin_slug );
            }

            //if the plugin is activated network wide in multisite, we need to process settings form submit ourselves
            if ( $this->network_activated ) {
                add_action( 'network_admin_edit_' . $this->plugin_slug, array ( $this, 'save_network_settings_page' ) );
            }

            add_action( 'admin_init', array ( $this, 'admin_init' ) );
            add_action( $this->menu_hook, array ( $this, 'admin_menu' ) );

            add_filter( 'plugin_action_links', array ( $this, 'add_action_links' ), 10, 2 );
            add_filter( 'network_admin_plugin_action_links', array ( $this, 'add_action_links' ), 10, 2 );
        }

        /**
         * Check if the plugin is activated network wide(in multisite).
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

        public function admin_menu () {
            add_submenu_page(
                $this->settings_page, __( 'BP Front Page widgets', 'bp-landing-pages' ), __( 'BP FP Widgets', 'bp-landing-pages' ), $this->capability, $this->plugin_slug, array ( $this, 'options_page' )
            );
        }

        public function options_page () {
            ?>
            <div class="wrap">
                <h2><?php _e( 'BP Front Page widgets', 'bp-landing-pages' ); ?></h2>
                <form method="post" action="<?php echo $this->form_action; ?>">

                    <?php
                    if ( $this->network_activated && isset( $_GET[ 'updated' ] ) ) {
                        echo "<div class='updated'><p>" . __( 'Settings updated.', 'bp-landing-pages' ) . "</p></div>";
                    }
                    ?>

                    <?php settings_fields( $this->option_name ); ?>
                    <?php do_settings_sections( __FILE__ ); ?>

                    <p class="submit">
                        <input name="bpfp_widgets_submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
                    </p>
                </form>
            </div>
            <?php
        }

        public function admin_init () {
            register_setting( $this->option_name, $this->option_name, array ( $this, 'plugin_options_validate' ) );

            add_settings_section( 'general_section', '', array ( $this, 'section_general' ), __FILE__ );
            add_settings_field( 'enabled_for', __( 'Enable landing pages for', 'bp-landing-pages' ), array ( $this, 'enabled_for' ), __FILE__, 'general_section' );
            add_settings_field( 'widgets_allowed', __( 'Widgets Allowed', 'bp-landing-pages' ), array ( $this, 'widgets_allowed' ), __FILE__, 'general_section' );

            add_settings_section( 'other_section', '', array ( $this, 'other_section' ), __FILE__ );
            add_settings_field( 'mobile_maxwidth', __( 'Mobile View Breakpoint', 'bp-landing-pages' ), array ( $this, 'mobile_maxwidth' ), __FILE__, 'other_section' );
        }

        public function section_general () {
            echo "<strong>" . __( 'General Settings', 'bp-landing-pages' ) . "</strong>";
        }

        public function other_section () {
            echo "<strong>" . __( 'Other Settings', 'bp-landing-pages' ) . "</strong>";
        }

        public function plugin_options_validate ( $input ) {
            return $input; //no validations for now
        }

        public function enabled_for () {
            $enabled_objects = $this->option( 'enabled_for' );
            if ( empty( $enabled_objects ) )
                $enabled_objects = array (); //make sure its an array

            $object_types = array ( 'members' => __( 'Members', 'bp-landing-pages' ) );
            if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
                $object_types[ 'groups' ] = __( 'Groups', 'bp-landing-pages' );
            }

            foreach ( $object_types as $object_type => $object_type_label ) {
                $checked = in_array( $object_type, $enabled_objects ) ? ' checked' : '';
                echo '<label><input type="checkbox" name="' . $this->option_name . '[enabled_for][]" value="' . $object_type . '" ' . $checked . '>' . $object_type_label . '</label><br>';
            }
        }

        public function widgets_allowed () {
            $registered_widgets = apply_filters( 'bpfp_widgets_registered_widgets', array () );

            $allowed_widgets = $this->option( 'widgets_allowed' );
            if ( empty( $allowed_widgets ) ) {
                $allowed_widgets = array ();
            }

            $object_types = array ( 'members' => __( 'Members', 'bp-landing-pages' ) );
            if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
                $object_types[ 'groups' ] = __( 'Groups', 'bp-landing-pages' );
            }

            foreach ( $object_types as $object_type => $object_type_label ) {
                echo "<strong>$object_type_label</strong><br><hr>";
                $allowed_widgets_current = isset( $allowed_widgets[ $object_type ] ) && !empty( $allowed_widgets[ $object_type ] ) ? $allowed_widgets[ $object_type ] : array ();
                foreach ( $registered_widgets as $widget_type => $widget_class ) {
                    $obj = new $widget_class;
                    if ( !$obj->is_available_for( $object_type ) ) {
                        continue;
                    }

                    $checked = in_array( $widget_type, $allowed_widgets_current ) ? ' checked' : '';
                    echo '<label><input type="checkbox" name="' . $this->option_name . '[widgets_allowed][' . $object_type . '][]" value="' . $widget_type . '" ' . $checked . '>' . $obj->get_name() . '</label><br>';
                    echo '<p class="description">' . $obj->get_description_admin() . '</p><br>';
                }
                echo '<br><br><br>';
            }
        }

        public function mobile_maxwidth () {
            $field_val = $this->option( 'mobile_maxwidth' );
            if ( !( int ) $field_val )
                $field_val = 800;

            echo '<input type="number" name="' . $this->option_name . '[mobile_maxwidth]" value="' . esc_attr( $field_val ) . '" >';
            echo '<p class="description">';
            _e( 'Screen width below which the widgets stack one below the other and ignore their \'width\' settings. This is required to make the widgets look good on mobile devices. You can adjust this value according to the design of your theme.', 'bp-landing-pages' );
            echo '</p>';
        }

        public function save_network_settings_page () {
            if ( !check_admin_referer( $this->option_name . '-options' ) )
                return;

            if ( !current_user_can( $this->capability ) )
                die( 'Access denied!' );

            if ( isset( $_POST[ 'bpfp_widgets_submit' ] ) ) {
                $submitted = stripslashes_deep( $_POST[ $this->option_name ] );
                $submitted = $this->plugin_options_validate( $submitted );

                update_site_option( $this->option_name, $submitted );
            }

            // Where are we redirecting to?
            $base_url = trailingslashit( network_admin_url() ) . 'settings.php';
            $redirect_url = add_query_arg( array ( 'page' => $this->plugin_slug, 'updated' => 'true' ), $base_url );

            // Redirect
            wp_redirect( $redirect_url );
            die();
        }

        public function add_action_links ( $links, $file ) {
            // Return normal links if not this plugin
            if ( plugin_basename( basename( constant( 'BPFPWIDGETS_PLUGIN_DIR' ) ) . '/loader.php' ) != $file ) {
                return $links;
            }

            $mylinks = array (
                '<a href="' . esc_url( $this->plugin_settings_url ) . '">' . __( "Settings", "bp-landing-pages" ) . '</a>',
            );
            return array_merge( $links, $mylinks );
        }

    }

    
	// End class BPFP_Widgets_Admin

endif;