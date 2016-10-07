<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget_RichContent' ) ):

    class BPFP_Widget_RichContent extends BPFP_Widget {

        public function __construct ( $args = '' ) {
            $this->_type = 'richcontent';
            $this->_name = "HTML Content";
            $this->_description = "Display html content area.";
            $this->_description_admin = __( "Displays a rich-text-editor on manage-landing-page screen, allowing input of html tags etc, but strips out scripts.", "bp-landing-pages" );
            $this->_available_for = array ( 'members', 'groups' );

            $this->_setup( $args );

            add_action( 'bpfp_widgets_update_data', array ( $this, 'filter_update_data' ), 10, 2 );
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
                'type' => 'wp_editor',
                'label' => __( 'Content', 'bp-landing-pages' ),
                'value' => !empty( $this->edit_field_value( 'content' ) ) ? $this->edit_field_value( 'content' ) : '',
            );

            return $fields;
        }

        public function filter_update_data ( $data, $widget ) {
            if ( $widget->get_id() != $this->get_id() )
                return $data;

            $field_name = 'content';

            //preserve allowed htm tags
            $html = @$_POST[ $field_name ];
            $html = wp_kses_post( $html );

            $data[ 'options' ][ $field_name ] = $html;

            return $data;
        }

    }

// End class BPFP_Widget_RichContent

endif;