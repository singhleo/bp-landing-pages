<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget_Content' ) ):

    class BPFP_Widget_Content extends BPFP_Widget {

        public function __construct ( $args = '' ) {
            $this->_type = 'content';
            $this->_name = "Text Area";
            $this->_description = __( "Display a text area.", "bp-landing-pages" );
            $this->_description_admin = __( "Displays a text area on manage-landing-page screen and outputs the entered text on landing page stripping out all html. If you've enabled 'HTML Content' widget, then you should keep this disabled.", "bp-landing-pages" );
            $this->_available_for = array ( 'members', 'groups' );

            $this->_setup( $args );
        }

        public function output () {
            $this->output_start();
            $this->output_title();
            ?>
            <div class="bpfp_w_content">
            <?php echo stripslashes( $this->_options[ 'content' ] ); ?>
            </div>
            <?php
            $this->output_end();
        }

        public function get_fields () {
            $fields = array ();

            $fields[ 'heading' ] = array (
                'type' => 'text',
                'label' => __( 'Heading', 'bp-landing-pages' ),
                'value' => !empty( $this->edit_field_value( 'heading' ) ) ? $this->edit_field_value( 'heading' ) : '',
            );

            $fields[ 'content' ] = array (
                'type' => 'textarea',
                'label' => __( 'Content', 'bp-landing-pages' ),
                'value' => !empty( $this->edit_field_value( 'content' ) ) ? $this->edit_field_value( 'content' ) : '',
                'is_required' => true
            );

            return $fields;
        }

    }

// End class BPFP_Widget_Content

endif;