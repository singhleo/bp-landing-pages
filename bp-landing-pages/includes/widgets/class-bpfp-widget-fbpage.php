<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

if ( !class_exists( 'BPFP_Widget_FBPage' ) ):

    class BPFP_Widget_FBPage extends BPFP_Widget {

        public function __construct ( $args = '' ) {
            $this->_type = 'fbpage';
            $this->_name = "Facebook Page";
            $this->_description = "The Page plugin lets you easily embed and promote any Facebook Page. Just like on Facebook, your visitors can like and share the Page without leaving your site.";
            $this->_available_for = array ( 'members', 'groups' );

            $this->_setup( $args );
        }

        public function output () {
            $fb_url = $this->_options[ 'url' ];

            if ( !$fb_url )
                return;

            $this->output_start();
            $this->output_title();
            ?>
            <div class="bpfp_w_content">
                <div id="fb-root"></div>
                <script>(function (d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id))
                            return;
                        js = d.createElement(s);
                        js.id = id;
                        js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.7&appId=551595108254181";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));</script>

                <div class="fb-page" 
                     data-href="<?php echo esc_url( $fb_url ); ?>" 
                     data-tabs="<?php echo!empty( $this->_options[ 'tabs' ] ) ? $this->_options[ 'tabs' ] : 'timeline'; ?>" 
            <?php if ( !empty( $this->_options[ 'theight' ] ) ) {
                echo "data-height='" . esc_attr( $this->_options[ 'theight' ] ) . "'";
            } ?> 
            <?php if ( !empty( $this->_options[ 'twidth' ] ) ) {
                echo "data-width='" . esc_attr( $this->_options[ 'twidth' ] ) . "'";
            } ?> 
                     data-small-header="<?php echo!empty( $this->_options[ 'smallheader' ] ) ? 'true' : 'false'; ?>" 
                     data-adapt-container-width="<?php echo!empty( $this->_options[ 'adaptwidth' ] ) ? 'true' : 'false'; ?>" 
                     data-hide-cover="<?php echo!empty( $this->_options[ 'hidecover' ] ) ? 'true' : 'false'; ?>" 
                     data-show-facepile="<?php echo!empty( $this->_options[ 'facepile' ] ) ? 'true' : 'false'; ?>" 
                     >
                    <blockquote cite="<?php echo esc_url( $fb_url ); ?>" class="fb-xfbml-parse-ignore">
                        <a href="<?php echo esc_url( $fb_url ); ?>"></a>
                    </blockquote>
                </div>
            </div>
            <?php
            $this->output_end();
        }

        public function get_fields () {
            return array (
                'url' => array (
                    'type' => 'url',
                    'label' => __( 'Facebook Page URL', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'url' ) ) ? $this->edit_field_value( 'url' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'The url of the facebook page', 'bp-landing-pages' ) ),
                ),
                'tabs' => array (
                    'type' => 'text',
                    'label' => __( 'Tabs', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'tabs' ) ) ? $this->edit_field_value( 'tabs' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'e.g: timeline, messages, events', 'bp-landing-pages' ) ),
                ),
                'theight' => array (
                    'type' => 'number',
                    'label' => __( 'Height', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'tabs' ) ) ? $this->edit_field_value( 'tabs' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'Height in pixels (optional)', 'bp-landing-pages' ) ),
                ),
                'twidth' => array (
                    'type' => 'number',
                    'label' => __( 'Width', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'tabs' ) ) ? $this->edit_field_value( 'tabs' ) : '',
                    'attributes' => array ( 'placeholder' => __( 'Width in pixels (optional)', 'bp-landing-pages' ) ),
                ),
                'smallheader' => array (
                    'type' => 'checkbox',
                    'label' => __( '', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'smallheader' ) ) ? $this->edit_field_value( 'smallheader' ) : '',
                    'options' => array ( 'yes' => __( 'Use Small Header', 'bp-landing-pages' ) ),
                ),
                'hidecover' => array (
                    'type' => 'checkbox',
                    'label' => __( '', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'hidecover' ) ) ? $this->edit_field_value( 'hidecover' ) : '',
                    'options' => array ( 'yes' => __( 'Hide Cover Photo', 'bp-landing-pages' ) ),
                ),
                'adaptwidth' => array (
                    'type' => 'checkbox',
                    'label' => __( '', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'adaptwidth' ) ) ? $this->edit_field_value( 'adaptwidth' ) : '',
                    'options' => array ( 'yes' => __( 'Adapt to plugin container width', 'bp-landing-pages' ) ),
                ),
                'facepile' => array (
                    'type' => 'checkbox',
                    'label' => __( '', 'bp-landing-pages' ),
                    'value' => !empty( $this->edit_field_value( 'facepile' ) ) ? $this->edit_field_value( 'facepile' ) : '',
                    'options' => array ( 'yes' => __( 'Show Friend\'s Faces', 'bp-landing-pages' ) ),
                ),
            );
        }

    }

// End class BPFP_Widget_ContentBlock

endif;