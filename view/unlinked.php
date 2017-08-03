<?php defined('ABSPATH') or die('No script kiddies please!'); ?>
<form id="link" method="post">
    <input type="hidden" name="form" value="link_product">
    <?= wp_nonce_field('settings') ?>
    <div class="row">
        <div class="col-sm-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-unlink"></i> <?php echo $b1_localization_strings['title_eshop_items']; ?></h3>
                </div>
                <div class="panel-body">
                    <table id="eshop-unlinked_items" class="pagination_shop table table-condensed table-hover">
                        <thead>
                        <tr>
                            <th></th>
                            <th>ID</th>
                            <th><?php echo $b1_localization_strings['label_name']; ?></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-sm-2 text-center">
            <button style="margin-bottom: 15px;" type="submit" class="btn btn-block btn-success btn-link-items">
                <i class="fa fa-link"></i> <?php echo $b1_localization_strings['text_link']; ?>
            </button>
        </div>
        <div class="col-sm-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-unlink"></i> <?php echo $b1_localization_strings['title_b1_items']; ?></h3>
                </div>
                <div class="panel-body">
                    <table id="b1-unlinked_items" class="pagination_b1 table table-condensed table-hover">
                        <thead>
                        <tr>
                            <th></th>
                            <th>ID</th>
                            <th><?php echo $b1_localization_strings['label_name']; ?></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-info" role="alert"><?php echo $b1_localization_strings['help_unlinked']; ?></div>
        </div>
    </div>
</form>
<script type="text/javascript">
    var frm = jQuery('#link');
    frm.submit(function (ev) {
        jQuery.ajax({
            type: frm.attr('method'),
            url: frm.attr('action'),
            data: frm.serialize(),
            success: function (data) {
                shop = jQuery('input[name=shop_item]:checked').val();
                b1 = jQuery('input[name=b1_item]:checked').val();
                jQuery('.pagination_shop').DataTable().ajax.reload();
                jQuery('.pagination_b1').DataTable().ajax.reload();
            }
        });
        ev.preventDefault();
    });
    jQuery("tr").click(function () {
        jQuery(this).find('input:radio').attr('checked', true);
    });
</script>