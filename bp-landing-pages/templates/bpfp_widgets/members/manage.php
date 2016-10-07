<?php 
do_action( 'before_bpfp_edit_widgets_contents', 'members' );

$is_checked = get_user_meta( bp_displayed_user_id(), '_has_custom_frontpage', true );
echo "<p><label><input type='checkbox' name='_has_custom_frontpage' value='yes' ". ( $is_checked ? 'checked' : '' ) ."> ";
_e( 'Enable custom front page.', 'bp-landing-pages' );
echo "</label></p>";
?>

<div class="bpfp_status hcfp_dependent" <?php if( !$is_checked ){ echo "style='display:none;'"; }?>>
    <p class="alert alert-success">
        <?php _e( 'Your custom front page is now live!', 'bp-landing-pages' );?>
        <?php echo " " . sprintf( "<a href='%s'>%s</a>", bp_displayed_user_domain(), __( 'View', 'bp-landing-pages' ) );?>
    </p>
</div>

<table class="table vtop tbl_widgets_ui_parent hcfp_dependent" <?php if( !$is_checked ){ echo "style='display:none;'"; }?>>
    <thead>
        <tr><td>Available Widgets</td><td>Widgets Added</td></tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <div class="bpfp_available_widgets">
                    <?php echo bpfp_widgets()->factory()->list_available_widgets();?>
                </div>
            </td>
            <td>
                <div class="bpfp_added_widgets">
                    <?php echo bpfp_widgets()->factory()->list_added_widgets();?>
                </div>
            </td>
        </tr>
    </tbody>
</table>

<?php do_action( 'after_bpfp_edit_widgets_contents', 'members' ); ?>