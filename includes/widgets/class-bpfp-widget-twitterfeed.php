<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget_TwitterFeed' ) ):

    class BPFP_Widget_TwitterFeed extends BPFP_Widget {

        public function __construct ( $args = '' ) {
            $this->_type = 'twitterfeed';
            $this->_name = "Twitter Feed";
            $this->_description = "Display your twitter feed.";
            $this->_available_for = array ( 'members' );

            $this->_setup( $args );
        }

        public function output () {
            $tw_handle = $this->_options[ 'username' ];

            if ( !$tw_handle )
                return;

            //remove the @ if found
            if ( strrpos( $tw_handle, '@', -strlen( $tw_handle ) ) !== false ) {
                $tw_handle = substr( $tw_handle, 1 );
            }

            $this->output_start();
            $this->output_title();
            ?>
            <div class="bpfp_w_content">
                <a class="twitter-timeline" 
                   <?php if ( !empty( $this->_options[ 'theme' ] ) ) {
                       echo "data-theme='" . esc_attr( $this->_options[ 'theme' ] ) . "'";
                   } ?>
            <?php if ( !empty( $this->_options[ 'linkcolor' ] ) ) {
                echo "data-link-color='" . esc_attr( $this->_options[ 'linkcolor' ] ) . "'";
            } ?>
            <?php if ( !empty( $this->_options[ 'theight' ] ) ) {
                echo "data-height='" . esc_attr( $this->_options[ 'theight' ] ) . "'";
            } ?>
                   href="https://twitter.com/<?php echo $tw_handle; ?>"><?php echo __( 'Tweets by', 'bp-landing-pages' ) . ' ' . $tw_handle; ?></a> 
                <script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
            </div>
            <?php
            $this->output_end();
        }

        public function get_fields () {
            return array (
                'username' => array (
                    'type' => 'text',
                    'label' => __( 'Twitter Handle', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'username' ) ) ? $this->edit_field_value( 'username' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'e.g: @johndoe', 'bp-landing-pages' ) ),
                ),
                'theight' => array (
                    'type' => 'number',
                    'label' => __( 'Height', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'theight' ) ) ? $this->edit_field_value( 'theight' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'Height in pixels (optional)', 'bp-landing-pages' ) ),
                ),
                'twidth' => array (
                    'type' => 'number',
                    'label' => __( 'Width', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'twidth' ) ) ? $this->edit_field_value( 'twidth' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'Width in pixels (optional)', 'bp-landing-pages' ) ),
                ),
                'theme' => array (
                    'type' => 'select',
                    'label' => __( 'Theme', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'theme' ) ) ? $this->edit_field_value( 'theme' ) : 'light',
                    'options' => array ( 'light' => __( 'Light', 'bp-landing-pages' ), 'dark' => __( 'Dark', 'bp-landing-pages' ) ),
                ),
                'linkcolor' => array (
                    'type' => 'select',
                    'label' => __( 'Default link color', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'linkcolor' ) ) ? $this->edit_field_value( 'linkcolor' ) : '#2B7BB9',
                    'options' => array (
                        '#981CEB' => __( 'Purple', 'bp-landing-pages' ),
                        '#19CF86' => __( 'Green', 'bp-landing-pages' ),
                        '#FAB81E' => __( 'Yellow', 'bp-landing-pages' ),
                        '#E95F28' => __( 'Orange', 'bp-landing-pages' ),
                        '#E81C4F' => __( 'Red', 'bp-landing-pages' ),
                        '#2B7BB9' => __( 'Blue', 'bp-landing-pages' ),
                    ),
                ),
            );
        }

    }

// End class BPFP_Widget_ContentBlock

endif;