<?php

if ( !defined( 'ABSPATH' ) )
    exit();

class BPFP_Widgets_Group extends BP_Group_Extension {

    function __construct () {
        $args = array (
            'enable_nav_item' => true,
            'screens' => array (
                'edit' => array (
                    'enabled' => true,
                    'slug' => 'front-page',
                    'name' => __( 'Front Page', 'bp-landing-pages' ),
                    'position' => 55,
                    'screen_callback' => array ( $this, 'settings_screen' ),
                    'screen_save_callback' => array ( $this, 'settings_screen_save' ),
                ),
                'create' => array ( 'enabled' => false ),
                'admin' => array ( 'enabled' => false ),
            ),
        );
        parent::init( $args );

        add_action( 'bp_after_group_admin_content', array ( $this, 'print_widgets_ui' ) );
    }

    function settings_screen ( $group_id = NULL ) {
        $is_checked = groups_get_groupmeta( $group_id, '_has_custom_frontpage', true );
        echo "<p><label><input type='checkbox' name='_has_custom_frontpage' value='yes' " . ( $is_checked ? 'checked' : '' ) . "> ";
        _e( 'Enable custom front page.', 'bp-landing-pages' );
        echo "</label></p>";
    }

    function settings_screen_save ( $group_id = NULL ) {
        $field_val = isset( $_POST[ '_has_custom_frontpage' ] ) ? $_POST[ '_has_custom_frontpage' ] : false;
        if ( 'yes' != $field_val )
            $field_val = ''; //not expecting anything else

        groups_update_groupmeta( $group_id, '_has_custom_frontpage', $field_val );
    }

    function print_widgets_ui () {
        if ( !bp_is_group_admin_screen( 'front-page' ) )
            return;

        $group_id = bp_get_group_id();

        $has_custom_frontpage = groups_get_groupmeta( $group_id, '_has_custom_frontpage', true );
        if ( !$has_custom_frontpage )
            return;

        include BPFPWIDGETS_PLUGIN_DIR . 'templates/bpfp_widgets/groups/manage-preloader.php';
    }

}

bp_register_group_extension( 'BPFP_Widgets_Group' );
