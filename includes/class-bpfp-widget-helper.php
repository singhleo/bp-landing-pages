<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget_Helper' ) ):

    class BPFP_Widget_Helper {

        protected
        /**
         * The variable to hold the class names for each type of widgets.
         * E.g 
         * [
         * 	'content_block' => 'BPFP_Widget_ContentBlock',
         *   'twitter_feed' => 'BBoss_Widget_TwitterFeed',
         * ]
         * @var array 
         * @since 1.0.0
         */
            $_widgets = array (),
            /**
             * meta key to store widgets settings for a given user or group
             */
            $_meta_key = 'bpfp_widgets';

        /**
         * Insures that only one instance of Class exists in memory at any
         * one time. Also prevents needing to define globals all over the place.
         *
         * @since 1.0.0
         *
         * @return object BPFP_Widget_Helper
         */
        public static function instance () {
            //Remember, this is called at bp_init hook !
            // Store the instance locally to avoid private static replication
            static $instance = null;

            // Only run these methods if they haven't been run previously
            if ( null === $instance ) {
                $instance = new BPFP_Widget_Helper();
            }

            // Always return the instance
            return $instance;
        }

        /**
         * A dummy constructor to prevent this class from being loaded more than once.
         *
         * @since 1.0.0
         */
        private function __construct () {
            add_action( 'bp_init', array ( $this, 'load_widgets' ), 80 );

            add_filter( 'bpfp_widgets_registered_widgets', array ( $this, 'list_default_widgets' ), 5 );

            add_action( 'wp_ajax_bpfp_widget_init', array ( $this, 'ajax_init_widget' ) );
            add_action( 'wp_ajax_bpfp_widget_update', array ( $this, 'ajax_update_widget' ) );
            add_action( 'wp_ajax_bpfp_widget_delete', array ( $this, 'ajax_delete_widget' ) );

            add_action( 'wp_ajax_bpfp_change_status', array ( $this, 'ajax_change_status' ) );

            add_action( 'wp_ajax_bpfp_widgets_reorder', array ( $this, 'ajax_reorder_widgets' ) );

            add_action( 'wp_ajax_bpfp_widgets_init_groups_ui', array ( $this, 'ajax_init_groups_ui' ) );
        }

        public function list_default_widgets ( $widgets ) {
            $widgets[ 'richcontent' ] = 'BPFP_Widget_RichContent';
            $widgets[ 'content' ] = 'BPFP_Widget_Content';
            $widgets[ 'twitterfeed' ] = 'BPFP_Widget_TwitterFeed';
            $widgets[ 'fbpage' ] = 'BPFP_Widget_FBPage';
            return $widgets;
        }

        /**
         * 
         */
        public function load_widgets () {
            $allowed_types = bpfp_widgets()->option( 'widgets_allowed' );

            if ( !empty( $allowed_types ) ) {
                //load the widget type parent class
                require_once( BPFPWIDGETS_PLUGIN_DIR . 'includes/widgets/abstract-bpfp-widget.php' );

                $registered_widgets = apply_filters( 'bpfp_widgets_registered_widgets', array () );
                if ( !empty( $registered_widgets ) ) {
                    $object_types = array ( 'members' => __( 'Members', 'bp-landing-pages' ) );
                    if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
                        $object_types[ 'groups' ] = __( 'Groups', 'bp-landing-pages' );
                    }

                    if ( empty( $object_types ) )
                        return;

                    foreach ( $object_types as $object_type => $object_type_label ) {
                        if ( !isset( $allowed_types[ $object_type ] ) || empty( $allowed_types[ $object_type ] ) ) {
                            continue;
                        }

                        foreach ( $registered_widgets as $widget_type => $widget_class ) {
                            if ( !in_array( $widget_type, $allowed_types[ $object_type ] ) ) {
                                continue;
                            }

                            if ( !isset( $this->_widgets[ $object_type ][ $widget_type ] ) ) {
                                $this->_widgets[ $object_type ][ $widget_type ] = $widget_class;
                            }
                        }
                    }
                }
            }
        }

        public function is_widget_available_for ( $my_widget_type, $object_type = '' ) {
            $is_available = false;
            if ( !$object_type ) {
                if ( bp_is_user() ) {
                    $object_type = 'members';
                } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                    $object_type = 'groups';
                }
            }

            if ( !$object_type )
                return $is_available;

            if ( !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                foreach ( $this->_widgets[ $object_type ] as $widget_type => $widget_class ) {
                    if ( $widget_type == $my_widget_type && class_exists( $widget_class ) ) {
                        $obj = new $widget_class;
                        $is_available = $obj->is_available_for( $object_type );
                        break;
                    }
                }
            }

            return $is_available;
        }

        /**
         * Check whether current user can manage( front page ) for the given object.
         * 
         * @param string $object_type 'members' or 'groups'
         * @param type $object_id meber or group id
         * 
         * @return boolean
         */
        protected function _current_user_can_manage ( $object_type, $object_id ) {
            $can_manage = false;

            if ( !is_user_logged_in() )
                return false;

            if ( current_user_can( 'manage_options' ) )
                return true; //admins can do all

            if ( 'members' == $object_type ) {
                if ( $object_id == get_current_user_id() ) {
                    $can_manage = true; //only own profile
                }
            } else if ( 'groups' == $object_type && bp_is_active( 'groups' ) ) {
                $can_manage = groups_is_user_admin( get_current_user_id(), $object_id );
            }

            return $can_manage;
        }

        /**
         * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         * Manage/edit widgets
         * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         */

        /**
         * 
         * @param type $object_id
         * @param type $object_type
         * @param type $widget_type
         * @return boolean
         */
        public function get_added_widgets_for ( $object_id, $object_type = 'members', $widget_type = '' ) {
            if ( 'members' == $object_type ) {
                $all_widgets = get_user_meta( $object_id, $this->_meta_key, true );
            } else if ( 'groups' == $object_type ) {
                $all_widgets = groups_get_groupmeta( $object_id, $this->_meta_key, true );
            }

            if ( empty( $all_widgets ) )
                return false;

            if ( !$widget_type )
                return $all_widgets;

            $filtered_widgets = array ();
            foreach ( $all_widgets as $widget ) {
                if ( $widget[ 'type' ] == $widget_type ) {
                    $filtered_widgets[] = $widget;
                }
            }

            return $filtered_widgets;
        }

        public function update_widgets_into_db ( $widgets, $object_id, $object_type = 'members' ) {
            if ( 'members' == $object_type ) {
                update_user_meta( $object_id, $this->_meta_key, $widgets );
            } else if ( 'groups' == $object_type ) {
                groups_update_groupmeta( $object_id, $this->_meta_key, $widgets );
            }
        }

        public function list_available_widgets () {
            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                return;

            if ( !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                foreach ( $this->_widgets[ $object_type ] as $widget_type => $widget_class ) {
                    if ( !class_exists( $widget_class ) )
                        continue;

                    $obj = new $widget_class;
                    if ( $obj->is_available_for( $object_type ) ) {
                        $obj->preview_screen();
                    }
                }
            }
        }

        public function list_added_widgets () {
            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                return;

            $has_added_widgets = false;
            /**
             * Will be an array of widgets.
             * Each widget will be an array
             * [
             *  'type' = 'content'
             *  'id'    = 1
             *  'options' = [
             *      'width'     = 100
             *      'heading'   = 'About Me'
             *      'content'   = 'Lorem ipsum ...'
             *  ]
             * ]
             */
            $added_widgets = $this->get_added_widgets_for( $object_id, $object_type );
            if ( !empty( $added_widgets ) && !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                $has_added_widgets = true;

                //if there are a lot of widgets added, we'll display them in collapsed mode.
                //For now, a lot means more than 2
                $display_args = array ( 'state' => 'expanded' );
                if ( count( $added_widgets ) > 2 ) {
                    $display_args[ 'state' ] = 'collapsed';
                }

                foreach ( $added_widgets as $added_widget ) {
                    foreach ( $this->_widgets[ $object_type ] as $widget_type => $widget_class ) {
                        if ( !class_exists( $widget_class ) )
                            continue;

                        if ( $widget_type == $added_widget[ 'type' ] ) {
                            $obj = new $widget_class( $added_widget );
                            if ( $obj->is_available_for( $object_type ) ) {
                                $obj->settings_screen( $display_args );
                            }
                            break;
                        }
                    }
                }
            }

            echo "<p class='no_widgets_message' style='display: " . ( $has_added_widgets ? 'none' : 'block' ) . "'>";
            _e( "No widgets added yet!", "bp-landing-pages" );
            echo "</p>";
        }

        public function ajax_init_widget () {
            check_ajax_referer( 'bpfp_widget_init', 'nonce' );
            $retval = array (
                'status' => false,
                'message' => __( 'Error', 'bp-landing-pages' ),
                'newnonce' => wp_create_nonce( 'bpfp_widget_init' ),
            );

            $type = isset( $_POST[ 'type' ] ) ? $_POST[ 'type' ] : '';
            if ( empty( $type ) ) {
                die( json_encode( $retval ) );
            }

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                die( json_encode( $retval ) );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            //1. get a widget object of this type
            $new_widget = false;
            if ( !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                foreach ( $this->_widgets[ $object_type ] as $widget_type => $widget_class ) {
                    if ( !class_exists( $widget_class ) )
                        continue;

                    if ( $widget_type == $type ) {
                        $obj = new $widget_class( array ( 'object_type' => $object_type, 'object_id' => $object_id ) );

                        if ( $obj->is_available_for( $object_type ) ) {
                            $new_widget = $obj;
                            break;
                        }
                    }
                }
            }

            if ( $new_widget ) {
                //2. Generate a new id for the new widget
                $new_widget->get_id();

                //3. Get settings html
                ob_start();
                $new_widget->settings_screen( array ( 'state' => 'expanded' ) );
                $retval[ 'html' ] = ob_get_clean();
                $retval[ 'status' ] = true;
            }

            die( json_encode( $retval ) );
        }

        public function ajax_update_widget () {
            check_ajax_referer( 'bpfp_widget_settings' );

            $retval = array (
                'status' => false,
                'message' => __( 'Error - Invalid data. Details could not be updated.', 'bp-landing-pages' ),
            );

            $widget_type = @$_POST[ 'widget_type' ];
            $widget_id = @$_POST[ 'widget_id' ];

            if ( empty( $widget_id ) || empty( $widget_type ) ) {
                die( json_encode( $retval ) );
            }

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                die( json_encode( $retval ) );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            //1. get the widget object
            $the_widget = false;
            if ( !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                foreach ( $this->_widgets[ $object_type ] as $type => $widget_class ) {
                    if ( !class_exists( $widget_class ) )
                        continue;

                    if ( $widget_type == $type ) {
                        $obj = new $widget_class( array ( 'object_type' => $object_type, 'object_id' => $object_id, 'id' => $widget_id ) );

                        if ( $obj->is_available_for( $object_type ) ) {
                            $the_widget = $obj;
                            break;
                        }
                    }
                }
            }

            if ( $the_widget ) {
                //2. update widget
                $retval = $the_widget->update();
                if ( !$retval[ 'status' ] ) {
                    die( json_encode( $retval ) );
                }

                //3. Get settings html
                ob_start();
                $the_widget->settings_screen( array ( 'state' => 'expanded' ) );
                $retval[ 'html' ] = ob_get_clean();
                $retval[ 'status' ] = true;
                $retval[ 'message' ] = __( 'Updated', 'bp-landing-pages' );
            }

            die( json_encode( $retval ) );
        }

        public function ajax_delete_widget () {
            check_ajax_referer( 'bpfp_widget_delete', 'nonce' );

            $id = @$_POST[ 'id' ];
            if ( empty( $id ) )
                die( 'Error' );

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                die( 'Error' );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            $widgets = $this->get_added_widgets_for( $object_id, $object_type );
            if ( !empty( $widgets ) ) {
                $new_widgets = array ();
                foreach ( $widgets as $widget ) {
                    if ( $widget[ 'id' ] != $id ) {
                        $new_widgets[] = $widget;
                    }
                }

                $this->update_widgets_into_db( $new_widgets, $object_id, $object_type );
            }

            die( json_encode( array (
                'status' => true,
                'newnonce' => wp_create_nonce( 'bpfp_widget_delete' ),
            ) ) );
        }

        public function ajax_change_status () {
            check_ajax_referer( 'bpfp_change_status', 'nonce' );

            $updated_status = @$_POST[ 'updated_status' ];

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                die( 'Error' );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            if ( 'members' == $object_type ) {
                update_user_meta( $object_id, '_has_custom_frontpage', $updated_status );
            } else if ( 'groups' == $object_type && bp_is_active( 'groups' ) ) {
                groups_update_groupmeta( $object_id, '_has_custom_frontpage', $updated_status );
            }

            die( json_encode( array (
                'status' => true,
                'newnonce' => wp_create_nonce( 'bpfp_change_status' ),
            ) ) );
        }

        public function ajax_reorder_widgets () {
            check_ajax_referer( 'bpfp_widgets_reorder', 'nonce' );

            $ids = @$_POST[ 'ids' ];

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) || empty( $ids ) )
                die( 'Error' );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            $ids = explode( ',', $ids );

            $widgets = $this->get_added_widgets_for( $object_id, $object_type );
            if ( !empty( $widgets ) ) {
                $new_widgets = array ();
                foreach ( $ids as $id ) {
                    foreach ( $widgets as $widget ) {
                        if ( $widget[ 'id' ] == $id ) {
                            $new_widgets[] = $widget;
                            break;
                        }
                    }
                }

                $this->update_widgets_into_db( $new_widgets, $object_id, $object_type );
            }

            die( json_encode( array (
                'status' => true,
                'newnonce' => wp_create_nonce( 'bpfp_change_status' ),
            ) ) );
        }

        public function ajax_init_groups_ui () {
            check_ajax_referer( 'bpfp_widgets_init_groups_ui', 'nonce' );

            if ( bp_is_user() ) {
                $object_type = 'members';
                $object_id = bp_displayed_user_id();
            } else if ( bp_is_active( 'groups' ) && bp_is_group() ) {
                $object_type = 'groups';
                $group = groups_get_current_group();
                $object_id = $group->id;
            }

            if ( empty( $object_id ) || empty( $object_type ) )
                die( 'Error' );

            if ( !$this->_current_user_can_manage( $object_type, $object_id ) )
                die( 'Erorr' );

            ob_start();
            bpfp_widget_load_template( 'groups/manage' );
            die( ob_get_clean() );
        }

        /*
         * +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         * output 
         * +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
         */

        public function print_widgets_output ( $object_id, $object_type ) {
            if ( empty( $object_id ) || empty( $object_type ) )
                return;

            $has_added_widgets = false;
            /**
             * Will be an array of widgets.
             * Each widget will be an array
             * [
             *  'type' = 'content'
             *  'id'    = 1
             *  'options' = [
             *      'width'     = 100
             *      'heading'   = 'About Me'
             *      'content'   = 'Lorem ipsum ...'
             *  ]
             * ]
             */
            $added_widgets = $this->get_added_widgets_for( $object_id, $object_type );
            if ( !empty( $added_widgets ) && !empty( $this->_widgets ) && isset( $this->_widgets[ $object_type ] ) && !empty( $this->_widgets[ $object_type ] ) ) {
                foreach ( $added_widgets as $added_widget ) {
                    foreach ( $this->_widgets[ $object_type ] as $widget_type => $widget_class ) {
                        if ( !class_exists( $widget_class ) )
                            continue;

                        if ( $widget_type == $added_widget[ 'type' ] ) {
                            $obj = new $widget_class( $added_widget );
                            if ( $obj->is_available_for( $object_type ) ) {
                                $obj->output();
                            }
                            break;
                        }
                    }
                }
            }
        }

    }

    

    

// End class BPFP_Widget_Helper

endif;