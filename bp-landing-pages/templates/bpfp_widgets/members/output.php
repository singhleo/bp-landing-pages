<?php
do_action( 'before_bpfp_widgets_output', 'members' );

$object_id = bp_displayed_user_id();
$object_type = 'members';

$widgets = bpfp_widgets()->factory()->get_added_widgets_for( $object_id, $object_type );
?>

<?php if ( !empty( $widgets ) ): ?>

    <div class="bpfp_widgets_wrapper clearfix">
        <?php bpfp_widgets()->factory()->print_widgets_output( $object_id, $object_type ); ?>
    </div>

<?php endif; ?>

<?php
do_action( 'after_bpfp_widgets_output', 'members' );