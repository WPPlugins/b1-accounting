<?php defined('ABSPATH') or die('No script kiddies please!'); ?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-link"></i> <?php echo $b1_localization_strings['label_linked_items']; ?>
                </h3>
            </div>
            <div class="panel-body">
                <table id="b1-linked_items" class="pagination_linked table table-condensed table-hover">
                    <thead>
                    <tr>
                        <th><?php echo $b1_localization_strings['label_eshop_id']; ?></th>
                        <th><?php echo $b1_localization_strings['label_b1_id']; ?></th>
                        <th><?php echo $b1_localization_strings['label_eshop_name']; ?></th>
                        <th><?php echo $b1_localization_strings['label_b1_name']; ?></th>
                        <th><?php echo $b1_localization_strings['label_b1_code']; ?></th>
                        <th></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="alert alert-info" role="alert">
            <?php echo $b1_localization_strings['help_linked']; ?>
        </div>
    </div>
</div>
<script>
    function reset_all() {
        if (window.confirm("Are you sure?")) {
            return true;
        } else {
            return false;
        }
    }
    function unlink(id) {
        jQuery.ajax({
            type: 'POST',
            data: {shop_item: id, form: 'unlink_product', _wpnonce: '<?= wp_create_nonce('settings'); ?>'},
            success: function (data) {
                jQuery('.pagination_linked').DataTable().ajax.reload();
            }
        });
        return false;
    }
</script>