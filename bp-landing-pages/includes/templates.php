<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
    exit;

function bpfp_widget_load_template ( $template ) {
    $template .= '.php';
    if ( file_exists( STYLESHEETPATH . '/bpfp_widgets/' . $template ) ) {
        include( STYLESHEETPATH . '/bpfp_widgets/' . $template );
    } else if ( file_exists( TEMPLATEPATH . '/bpfp_widgets/' . $template ) ) {
        include( TEMPLATEPATH . '/bpfp_widgets/' . $template );
    } else {
        include( BPFPWIDGETS_PLUGIN_DIR . 'templates/bpfp_widgets/' . $template );
    }
}

function bpfp_widget_buffer_template_part ( $template, $echo = true ) {
    ob_start();

    bpfp_widget_load_template( $template );
    // Get the output buffer contents
    $output = ob_get_clean();

    // Echo or return the output buffer contents
    if ( true === $echo ) {
        echo $output;
    } else {
        return $output;
    }
}

add_action( 'before_bpfp_widgets_output', 'bpfp_widgets_responsive_css' );

function bpfp_widgets_responsive_css () {
    $mobile_maxwidth = bpfp_widgets()->option( 'mobile_maxwidth' );
    if ( ( int ) $mobile_maxwidth > 0 ) {
        ?>
        <style type="text/css">
            @media screen and (max-width: <?php echo ( int ) $mobile_maxwidth; ?>px) {
                .bpfp_widget_output{
                    float: none !important;
                    width: 100% !important;
                }
            }
        </style>
        <?php
    }
}

if ( !function_exists( 'emi_generate_fields' ) ):
    /*
     * function to generate the html for given form fields
     *
     * @param array $fields
      an example input
      $fields = array(
      'full_name'     => array(
      'type'          => 'textbox',
      'label'         => 'Full Name',
      'sqlcolumn'     => 'user_name',
      'attributes'    => array(
      'class'=>'inputtype1',
      'placeholder'=>'Full Name'
      ),
      'value'         => 'Mr. XYZ',
      'description'   => 'Enter your full name including your surname.'
      ),

      'date_of_birth' => array(
      'type'          => 'textbox',
      'label'         => 'Date of Birth',
      'sqlcolumn'     => 'user_dob',
      'attributes'    => array(
      'class'=>'jqueryui-date'
      )
      ),

      'gender'        => array(
      'type'          => 'radio',
      'label'         => 'Gender',
      'sqlcolumn'     => 'user_gender'
      'options'       => array(
      'male'      => 'Male',
      'female'    => 'Female'
      ),
      'value'         => 'female'
      ),

      'hobbies'        => array(
      'type'          => 'select',
      'label'         => 'Hobbies',
      'sqlcolumn'     => 'user_hobbies',
      'attributes'    => array(
      'multiple'=>''
      )
      'options'       => array(
      '11'     => 'Listening to music',
      '16'     => 'playing games',
      '5'     => 'Reading',
      ),
      'value'         => array( '5', '11' )
      ),
      );

     * 
     * @param array $args options
     * @return void
     */

    function emi_generate_fields ( $fields, $args = "" ) {

        if ( !$fields || empty( $fields ) )
            return;
        if ( !$args || empty( $args ) )
            $args = array ();

        $defaults = array (
            'before_list' => '',
            'after_list' => '',
            'before_field' => '',
            'after_field' => '',
            'form_id' => 'form1'
        );

        $args = array_merge( $defaults, $args );

        echo $args[ 'before_list' ];

        foreach ( $fields as $field_id => $field ) {
            $field_defaults = array (
                'label' => '',
                'before' => '',
                'before_inside' => '',
                'after_inside' => '',
                'after' => '',
                'wrapper_class' => '',
                'type' => 'text',
            );

            $field = wp_parse_args( $field, $field_defaults );

            echo $args[ 'before_field' ];

            echo $field[ 'before' ];

            $cssclass = 'field field-' . $field_id . ' field-' . $field[ 'type' ];
            if ( $field[ 'wrapper_class' ] ) {
                $cssclass .= " " . $field[ 'wrapper_class' ];
            }

            echo "<div class='$cssclass' id='field-" . $field_id . "'>";
            echo $field[ 'before_inside' ];

            switch ( $field[ 'type' ] ) {
                case 'checkbox':
                case 'radio':
                    //label
                    $html = "<label>" . $field[ 'label' ] . "</label>";
                    foreach ( $field[ 'options' ] as $option_val => $option_label ) {
                        $html .= "<label class='label_option label_option_" . $field[ 'type' ] . "'><input type='" . $field[ 'type' ] . "' name='" . $field_id . "[]' value='$option_val' id='$option_val'";

                        //checked ?
                        if ( isset( $field[ 'value' ] ) && !empty( $field[ 'value' ] ) ) {
                            if ( is_array( $field[ 'value' ] ) ) {
                                if ( in_array( $option_val, $field[ 'value' ] ) )
                                    $html .= " checked='checked'";
                            }
                            else {
                                if ( $option_val == $field[ 'value' ] )
                                //$html .= " checked='checked'";  
                                    $html .= "";
                            }
                        }

                        //attributes
                        if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                            foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                                $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                            }
                        }

                        $html .= " />$option_label</label>";
                    }

                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;

                case 'select':

                    //label
                    $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";
                    $html .= "<select id='$field_id' name='$field_id'";

                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }

                    $html .= ">";

                    foreach ( $field[ 'options' ] as $option_val => $option_label ) {
                        $html .= "<option value='$option_val' ";

                        //checked ?
                        if ( isset( $field[ 'value' ] ) && !empty( $field[ 'value' ] ) ) {
                            if ( is_array( $field[ 'value' ] ) ) {
                                if ( in_array( $option_val, $field[ 'value' ] ) )
                                    $html .= " selected='selected'";
                            }
                            else {
                                if ( $option_val == $field[ 'value' ] )
                                    $html .= " selected='selected'";
                            }
                        }

                        $html .= ">$option_label</option>";
                    }

                    $html .= "</select>";

                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;
                case 'textarea':
                    //label
                    $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";

                    $html .= "<textarea type='text' id='$field_id' name='$field_id' ";
                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }
                    $html .= " >";

                    //selected value
                    $field[ 'value' ] = esc_textarea( $field[ 'value' ] );
                    if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                        $html .= $field[ 'value' ];
                    }

                    $html .= "</textarea>";

                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;

                case 'wp_editor':
                    //label
                    $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";

                    $html .= "<textarea type='text' id='$field_id' name='$field_id' ";
                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            if ( 'style' == $att_name ) {
                                continue;
                            }
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }

                    //$html .= " style='display:none'";
                    $html .= " >";

                    //selected value
                    $field[ 'value' ] = esc_textarea( $field[ 'value' ] );
                    if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                        $html .= $field[ 'value' ];
                    }

                    $html .= "</textarea>";

                    //$html .= "<div class='content-editor' id='editor-$field_id' data-for='$field_id'>" . $field['value'] . "</div>";
                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;

                case 'button':
                case 'submit' :
                    $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";

                    $field_type = 'submit';
                    if ( isset( $field[ 'type' ] ) ) {
                        $field_type = $field[ 'type' ];
                    }

                    if ( $field_type == 'button' ) {
                        $html .= "<button id='$field_id' name='$field_id'";
                    } else {
                        $html .= "<input type='$field_type' id='$field_id' name='$field_id'";
                    }

                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }

                    if ( $field_type == 'button' ) {
                        $html .= ">";
                        if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                            $html .= $field[ 'value' ];
                        }
                        $html .= "</button>";
                    } else {
                        if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                            $html .= " value='" . esc_attr( $field[ 'value' ] ) . "'";
                        }
                        $html .= " />";
                    }

                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;

                case 'repeater':

                    //label


                    $html = "";

                    $html .= "<input type='hidden' data-isrepeater='1' id='$field_id' name='$field_id'";

                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }

                    //selected value
                    if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                        $html .= " value='" . esc_attr( $field[ 'value' ] ) . "'";
                    }

                    $html .= " />";
                    echo $html;

                    if ( function_exists( 'do_action' ) ) {
                        $actionname = 'emi_generate_form_repeater_' . $field_id;
                        do_action( $actionname, $field, $args[ 'form_id' ] );
                    }

                    break;

                case 'label':
                    echo $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";
                    break;

                default:
                    //label
                    $html = "<label for='$field_id'>" . $field[ 'label' ] . "</label>";

                    $html .= "<input type='{$field[ 'type' ]}' id='$field_id' name='$field_id'";

                    //attributes
                    if ( isset( $field[ 'attributes' ] ) && !empty( $field[ 'attributes' ] ) ) {
                        foreach ( $field[ 'attributes' ] as $att_name => $att_val ) {
                            $html .= " $att_name='" . esc_attr( $att_val ) . "'";
                        }
                    }

                    //selected value
                    $field[ 'value' ] = $field[ 'value' ];
                    if ( isset( $field[ 'value' ] ) && $field[ 'value' ] ) {
                        $html .= " value='" . esc_attr( $field[ 'value' ] ) . "'";
                    }

                    $html .= " />";

                    //description
                    if ( isset( $field[ 'description' ] ) && $field[ 'description' ] ) {
                        $html .= "<span class='field_description'>" . $field[ 'description' ] . "</span>";
                    }

                    echo $html;
                    break;
            }

            echo $field[ 'after_inside' ];
            echo "</div><!-- .field -->";

            echo $field[ 'after' ];
            echo $args[ 'after_field' ];
        }

        echo $args[ 'after_list' ];
    }



endif;