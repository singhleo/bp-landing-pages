<?php do_action( 'before_bpfp_edit_widgets_contents', 'groups' ); ?>

<table class="table vtop">
    <thead>
        <tr><td>Available Widgets</td><td>Widgets Added</td></tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <div class="bpfp_available_widgets">
                    <?php echo bpfp_widgets()->factory()->list_available_widgets(); ?>
                </div>
            </td>
            <td>
                <div class="bpfp_added_widgets">
                    <?php echo bpfp_widgets()->factory()->list_added_widgets(); ?>
                </div>
            </td>
        </tr>
    </tbody>
</table>

<?php do_action( 'after_bpfp_edit_widgets_contents', 'groups' ); ?>