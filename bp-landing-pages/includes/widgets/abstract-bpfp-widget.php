<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget' ) ):

    abstract class BPFP_Widget {

        protected
        /**
         * Widget type - A key to differentiate it from other widget types. E.g: contentblock, twitter_block etc.
         * This must be unique across all widgets.
         */
            $_type = '',
            /**
             * A descriptive, human-friendly name :)
             */
            $_name = '',
            /**
             * Description. What the widget does, etc.
             */
            $_description = '',
            /**
             * Description for admins, displayed on admin screen.
             */
            $_description_admin = '',
            /**
             * Whether this widget is available for 'members', 'groups' or both. Should be an array.
             */
            $_available_for = array ( 'members' ),
            /**
             * Whether this widget can be added more than once on a page. Default true.
             */
            $_is_multiple = true,
            /**
             * An id generated at runtime. Useful for widgets which can be added more than once.
             */
            $_id = '',
            /**
             * whether this widget is added for a member or group
             */
            $_object_type = 'members',
            /**
             * if of the user or group this widget is added to
             */
            $_object_id = false,
            /**
             * Data for all fields/settings of the widget
             */
            $_options = array ();

        protected function _setup ( $args = '' ) {
            if ( empty( $args ) )
                return;

            $this->_id = isset( $args[ 'id' ] ) && !empty( $args[ 'id' ] ) ? $args[ 'id' ] : false;
            $this->_options = isset( $args[ 'options' ] ) && !empty( $args[ 'options' ] ) ? $args[ 'options' ] : false;
            $this->_object_type = isset( $args[ 'object_type' ] ) && !empty( $args[ 'object_type' ] ) ? $args[ 'object_type' ] : 'members';
            $this->_object_id = isset( $args[ 'object_id' ] ) && !empty( $args[ 'object_id' ] ) ? $args[ 'object_id' ] : false;

            add_filter( 'bpfp_widgets_update_validation_errors', array ( $this, 'validation_basic' ), 5, 2 );
        }

        /**
         * Update properties by fetching corresponding values from db
         * @param boolean $force Whether to override and update values from database
         */
        protected function _update_from_db ( $force = false ) {
            //if there is no id, it doesn't exist in database yet, for sure.       
            if ( $this->get_id() ) {
                if ( empty( $this->_options ) || $force ) {
                    $widgets = bpfp_widgets()->factory()->get_added_widgets_for( $this->_object_id, $this->_object_type, $this->get_type() );
                    if ( !empty( $widgets ) ) {
                        foreach ( $widgets as $widget ) {
                            if ( $widget[ 'id' ] == $this->get_id() ) {
                                //update values from db
                                $this->_options = $widget[ 'options' ];

                                break;
                            }
                        }
                    }
                }
            }
        }

        public function get_type () {
            return $this->_type;
        }

        public function get_name () {
            return $this->_name;
        }

        public function get_description () {
            return $this->_description;
        }

        /**
         * Get the description of widget intended to be displayed to admins.
         * If admin description is not provided, it falls back to normal description.
         * 
         * @return string
         */
        public function get_description_admin () {
            $description = $this->_description_admin;
            if ( empty( $description ) ) {
                $description = $this->_description;
            }

            return $description;
        }

        public function get_id () {
            if ( empty( $this->_id ) ) {
                //$this->_id = $this->_generate_new_widget_id();
                $this->_id = md5( microtime() );
            }
            return $this->_id;
        }

        public function edit_field_value ( $field_name ) {
            $val = isset( $this->_options[ $field_name ] ) ? $this->_options[ $field_name ] : '';
            $val = stripslashes( $val );
            return apply_filters( 'bpfp_widget_edit_field_value', $val, $field_name, $this );
        }

        public function view_field_val ( $field_name ) {
            //for now, its same as edit_field_val
            $val = isset( $this->_options[ $field_name ] ) ? $this->_options[ $field_name ] : '';
            $val = stripslashes( $val );
            return apply_filters( 'bpfp_widget_view_field_value', $val, $field_name, $this );
        }

        public function is_available_for ( $object_type = 'members' ) {
            $flag = !empty( $this->_available_for ) && in_array( $object_type, $this->_available_for ) ? true : false;
            return apply_filters( 'bpfp_widgets_is_available_for', $flag, $object_type, $this );
        }

        public function is_multiple_allowed () {
            return apply_filters( 'bpfp_widgets_is_multiple_allowed', $this->_is_multiple, $this );
        }

        protected function _generate_new_widget_id () {
            $last_id = $this->_get_last_id( $this->_object_type, $this->_object_id );
            $new_id = $last_id++;

            $this->_id = $this->get_type() . '_' . $new_id;
            return $this->_id;
        }

        protected function _get_last_id ( $object_type, $object_id ) {
            $last_id = 0;
            $widgets = bpfp_widgets()->factory()->get_added_widgets_for( $object_id, $object_type );
            if ( !empty( $widgets ) ) {
                foreach ( $widgets as $widget ) {
                    $id = ( int ) $widget[ 'id' ];
                    if ( $id > $last_id ) {
                        $last_id = $id;
                    }
                }
            }

            return $last_id;
        }

        /**
         * Update widget settings
         * 
         * @param string $object_type
         * @param int $object_id
         * 
         * @return array {
         *      @type boolean status
         *      @type string message
         * }
         */
        public function update () {
            $retval = array ( 'status' => false, 'message' => '' );
            if ( empty( $this->_id ) ) {
                $this->get_id();
            }

            $validation_errors = apply_filters( 'bpfp_widgets_update_validation_errors', '', $this );

            if ( !empty( $validation_errors ) ) {
                $retval[ 'message' ] = implode( '<br>', $validation_errors );
                return $retval;
            }

            /**
             * A widget is saved in database as an associative array.
             * The structure is 
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
            $widget_db = array (
                'type' => $this->get_type(),
                'id' => $this->get_id(),
                'last_updated' => bp_core_current_time(),
                'options' => array (
                    'width' => @$_POST[ 'width' ],
                ),
            );

            $excluded_types = array ( 'label' ); //and any other field type that we needn't save in db
            $fields = $this->get_fields();
            if ( !empty( $fields ) ) {
                foreach ( $fields as $field_name => $field_attr ) {
                    if ( in_array( $field_attr[ 'type' ], $excluded_types ) )
                        continue;

                    $field_value = @$_POST[ $field_name ];
                    if ( $field_value ) {
                        $field_value = sanitize_text_field( $field_value );
                    }

                    $widget_db[ 'options' ][ $field_name ] = $field_value;
                }
            }

            $widget_db = apply_filters( 'bpfp_widgets_update_data', $widget_db, $this );

            $this->_options = $widget_db[ 'options' ];
            $this->_update_db( $widget_db );

            return array ( 'status' => true, 'message' => __( 'Updated', 'bp-landing-pages' ) );
        }

        protected function _update_db ( $widget_db ) {
            $new_widgets = array ();
            $is_new = false;

            $added_widgets = bpfp_widgets()->factory()->get_added_widgets_for( $this->_object_id, $this->_object_type );
            if ( !empty( $added_widgets ) ) {
                $is_new = true;

                foreach ( $added_widgets as $added_widget ) {
                    if ( $added_widget[ 'id' ] == $widget_db[ 'id' ] ) {
                        $is_new = false;
                        $new_widgets[] = $widget_db; //updated one
                    } else {
                        $new_widgets[] = $added_widget; //old one
                    }
                }
            } else {
                $is_new = true;
            }

            if ( $is_new ) {
                $new_widgets[] = $widget_db;
            }

            bpfp_widgets()->factory()->update_widgets_into_db( $new_widgets, $this->_object_id, $this->_object_type );
        }

        /**
         * Performs basic validation on form fields before updating.
         * 
         * @param array $errors
         * @param BPFP_Widget $obj
         * @return string
         */
        public function validation_basic ( $errors, $obj ) {
            if ( !empty( $errors ) )
                return $errors; //no need to check if there already are some validation errors




                
//id
            if ( !$obj->get_id() )
                $errors[] = __( 'Invalid form data.', 'bp-landing-pages' );

            //width
            if ( isset( $_POST[ 'width' ] ) && !empty( $_POST[ 'width' ] ) ) {
                $width = intval( $_POST[ 'width' ] );
                if ( $width < 1 || $width > 100 ) {
                    $errors[] = __( "Width must be between 1 and 100.", 'bp-landing-pages' );
                }
            } else {
                $errors[] = __( "Width must be between 1 and 100.", 'bp-landing-pages' );
            }

            //required fields
            $fields = $this->get_fields();
            if ( !empty( $fields ) ) {
                foreach ( $fields as $field_name => $field_attr ) {
                    if ( isset( $field_attr[ 'is_required' ] ) && $field_attr[ 'is_required' ] ) {
                        if ( empty( $_POST[ $field_name ] ) ) {
                            $errors[] = sprintf( __( "%s can not be empty.", "bp-landing-pages" ), $field_attr[ 'label' ] );
                        }
                    }
                }
            }

            return $errors;
        }

        public function preview_screen () {
            ?>
            <div class="bpfp_widget preview bpfp_widget-<?php echo $this->get_type(); ?>" data-type="<?php echo $this->get_type(); ?>">
                <div class="widget_name">
                    <span>
            <?php echo $this->get_name(); ?>
                        <a href="#" class="lnk_add_widget" title="<?php _e( 'Add', 'bp-landing-pages' ); ?>">+</a>
                    </span>
                </div>
                <div class="widget_description"><?php echo $this->get_description(); ?></div>
                <div class="loading_overlay"><span class="helper"></span><img src="<?php echo network_home_url( 'wp-includes/images/spinner.gif' ); ?>" ></div>
            </div>
            <?php
        }

        public function settings_screen ( $args = '' ) {
            $defaults = array (
                'state' => 'collapsed',
            );
            $args = wp_parse_args( $args, $defaults );

            $this->_update_from_db();

            $css_class = "bpfp_widget settings";
            $css_class .= " bpfp_widget-" . $this->get_type();
            $css_class .= " " . $args[ 'state' ];
            ?>
            <div class="<?php echo $css_class; ?>" id="<?php echo $this->get_type() . '_' . $this->get_id(); ?>" >
                <div class="widget_name">
                    <span>
            <?php echo $this->get_name(); ?>
                        <a href="#" class="lnk_toggle_widget_details"></a>
                    </span>
                </div>
                <div class="widget_description" <?php
                if ( 'collapsed' == $args[ 'state' ] ) {
                    echo "style='display:none;'";
                }
                ?>>

                    <form method="POST" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" id="frm_bpfp_widget_settings">
            <?php wp_nonce_field( 'bpfp_widget_settings', '_wpnonce', false ); ?>
                        <input type="hidden" name="widget_type" value="<?php echo $this->get_type(); ?>" >
                        <input type="hidden" name="widget_id" value="<?php echo $this->get_id(); ?>" >
                        <input type="hidden" name="action" value="bpfp_widget_update" >

                        <div class="widget_fields">
                            <?php
                            $delete_link = sprintf( "<a class='lnk_delete_widget' href='#' title='%s'>%s</a>", __( 'Delete this widget', 'bp-landing-pages' ), __( 'Delete', 'bp-landing-pages' ) );
                            $fields = array_merge( $this->_get_default_fields(), $this->get_fields() );
                            $fields[ 'update' ] = array (
                                'type' => 'submit',
                                'value' => __( 'Update', 'bp-landing-pages' ),
                                'after_inside' => $delete_link,
                            );

                            if ( !empty( $fields ) ) {
                                emi_generate_fields( $fields );
                            }
                            ?>
                        </div>

                    </form>

                </div>
                <div class="loading_overlay">
                    <span class="helper"></span>
                    <img src="<?php echo network_home_url( 'wp-includes/images/spinner.gif' ); ?>" class="img_loading" >
                    <img src="<?php echo BPFPWIDGETS_PLUGIN_URL . 'assets/done.png'; ?>" class="img_success" style="display: none;" >
                </div>
            </div>
            <?php
        }

        protected function _get_default_fields () {
            $fields = array ();
            $fields[ 'width' ] = array (
                'type' => 'number',
                'label' => __( 'Width', 'bp-landing-pages' ),
                'after_inside' => '%',
                'after' => '<hr>',
                'value' => !empty( $this->edit_field_value( 'width' ) ) ? $this->edit_field_value( 'width' ) : 100,
            );

            return $fields;
        }

        public abstract function get_fields ();

        public abstract function output ();

        public function output_start () {
            $style_attr = array ();

            $width = ( float ) $this->_options[ 'width' ] > 0 ? ( float ) $this->_options[ 'width' ] : 100;
            $props = apply_filters( 'bpfp_widget_inline_style', array (
                'float' => 'left',
                'width' => $width . '%',
                ), $this );

            if ( !empty( $props ) ) {
                foreach ( $props as $k => $v ) {
                    $style_attr[] = "$k:$v";
                }
            }

            $style_attr = !empty( $style_attr ) ? implode( ';', $style_attr ) : "";
            ?>
            <div class = "bpfp_widget_output type-<?php echo $this->get_type(); ?>" 
                 id = "<?php echo $this->get_id(); ?>" 
                 style = "<?php echo $style_attr; ?>" >
            <?php
        }

        public function output_end () {
            echo '</div><!-- .bpfp_widget_output -->';
        }

        public function output_title () {
            /**
             * This assumes that all widgets will name their title field as 'heading'
             */
            $heading = isset( $this->_options[ 'heading' ] ) ? $this->_options[ 'heading' ] : '';
            if ( empty( $heading ) )
                return;

            echo "<div class='bpfp_w_title'><h4>" . $this->_options[ 'heading' ] . "</h4></div>";
        }

    }

// End class BPFP_Widget

endif;