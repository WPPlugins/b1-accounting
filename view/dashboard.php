<?php
defined('ABSPATH') or die('No script kiddies please!');
wp_enqueue_script('bootstap', plugins_url('assets/js/bootstrap.min.js', dirname(__FILE__)));
wp_enqueue_script('bootstap', plugins_url('assets/js/bootstrap.min.js', dirname(__FILE__)));
wp_enqueue_script('dataTables', plugins_url('assets/js/jquery.dataTables.min.js', dirname(__FILE__)));
wp_enqueue_style('bootstrap', plugins_url('assets/css/bootstrap.min.css', dirname(__FILE__)));
wp_enqueue_style('fontAwesome', plugins_url('assets/css/font-awesome.min.css', dirname(__FILE__)));
wp_enqueue_style('dataTables', plugins_url('assets/css/jquery.dataTables.min.css', dirname(__FILE__)));
?>
<style>
    .wp-admin select {
        height: 34px;
    }
</style>

<div id="content" class="b1-extension">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <a href="mailto:<?= $settings_contact_email; ?>" data-toggle="tooltip" title="<?= $b1_localization_strings['button_contact']; ?>" class="btn btn-default">
                    <i class="fa fa-envelope"></i>
                    <span class="hidden-xs"><?= $b1_localization_strings['button_contact']; ?></span>
                </a>
                <a href="<?= $settings_documentation_url; ?>" data-toggle="tooltip" title="<?= $b1_localization_strings['button_documentation']; ?>" target="_blank" class="btn btn-default">
                    <i class="fa fa-book"></i>
                    <span class="hidden-xs"><?= $b1_localization_strings['button_documentation']; ?></span>
                </a>
                <a href="<?= $settings_help_page_url; ?>" data-toggle="tooltip" title="<?= $b1_localization_strings['button_help_page']; ?>" target="_blank" class="btn btn-default">
                    <i class="fa fa-book"></i>
                    <span class="hidden-xs"><?= $b1_localization_strings['button_help_page']; ?></span>
                </a>
            </div>
            <h1><?php echo $b1_localization_strings['heading_title']; ?></h1>
        </div>
    </div>
    <div class="container-fluid">
        <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 40px;">
            <li class="active">
                <a href="#settings" data-toggle="tab">
                    <span class="text-primary"><i class="fa fa-cogs"></i></span>
                    <span class="text-primary hidden-xs"><?= $b1_localization_strings['label_settings'] ?></span>
                </a>
            </li>
            <li>
                <a href="#unlinked_items" data-toggle="tab">
                    <span class="text-warning"><i class="fa fa-unlink"></i></span>
                    <span class="text-warning hidden-xs"><?= $b1_localization_strings['label_unlinked_items'] ?></span>
                </a>
            </li>
            <li>
                <a href="#linked_items" data-toggle="tab">
                    <span class="text-success"><i class="fa fa-link"></i></span>
                    <span class="text-success hidden-xs"><?= $b1_localization_strings['label_linked_items'] ?></span>
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="settings">
                <?php include plugin_dir_path(__FILE__) . 'settings.php' ?>
            </div>
            <div class="tab-pane" id="unlinked_items">
                <?php include plugin_dir_path(__FILE__) . 'unlinked.php' ?>
            </div>
            <div class="tab-pane" id="linked_items">
                <?php include plugin_dir_path(__FILE__) . 'linked.php' ?>
            </div>
        </div>
    </div>
</div>
<?php
if ($settings_relation_type == 'more_1') {
    $input_type = 'checkbox';
} else {
    $input_type = 'radio';
}
?>
<script>
    jQuery(document).ready(function () {
        jQuery('.pagination_shop').DataTable({
            "pagingType": "full_numbers",
            "processing": true,
            "serverSide": true,
            "searching": false,
            "ordering": false,
            "ajax": "<?= str_replace('&amp;', '&', wp_nonce_url($_SERVER['REQUEST_URI'] . "&type=pagination_shop", 'cron')) ?>",
            <?= $datatables_translation ?>
            "columns": [
                {
                    "data": "id",
                    "render": function (data, type, full, meta) {
                        <?php if ($input_type == 'checkbox') { ?>
                        return '<input type="checkbox" name="shop_item[]" value="' + data + '">';
                        <?php } else { ?>
                        return '<input type="radio" name="shop_item" value="' + data + '">';
                        <?php } ?>
                    }
                },
                {"data": "id"},
                {"data": "name"},
            ]
        });
        jQuery('.pagination_b1').DataTable({
            "pagingType": "full_numbers",
            "processing": true,
            "serverSide": true,
            "searching": false,
            "ordering": false,
            "ajax": "<?= str_replace('&amp;', '&', wp_nonce_url($_SERVER['REQUEST_URI'] . "&type=pagination_b1", 'cron')) ?>",
            <?= $datatables_translation ?>
            "columns": [
                {
                    "data": "id",
                    "render": function (data, type, full, meta) {
                        return '<input type="radio" name="b1_item" value="' + data + '">';
                    }
                },
                {"data": "id"},
                {"data": "name"},
            ]
        });
        jQuery('.pagination_linked').DataTable({
            "pagingType": "full_numbers",
            "processing": true,
            "serverSide": true,
            "searching": false,
            "ordering": false,
            "ajax": "<?= str_replace('&amp;', '&', wp_nonce_url($_SERVER['REQUEST_URI'] . "&type=pagination_linked", 'cron')) ?>",
            <?= $datatables_translation ?>
            "columns": [
                {"data": "id"},
                {"data": "b1_reference_id"},
                {"data": "name"},
                {"data": "b1_name"},
                {"data": "upc"},
                {
                    "data": "id",
                    "render": function (data, type, full, meta) {
                        return '<form method="POST">' +
                            '<input type="hidden" name="form" value="unlink_product">' +
                            '<input type="hidden" name="shop_item" value="' + data + '">' +
                            '<button onclick="return unlink(' + data + ')" type="submit" class="btn btn-success btn-sm"><?php echo $b1_localization_strings['unlink']; ?></button>' +
                            '</form>';
                    }
                }
            ]
        });

    });
    jQuery(document).on("click", "tr", function (e) {
        jQuery(this).find('input:radio').attr('checked', true);
        var chk = jQuery(this).find('input:checkbox').get(0);
        if (chk !== undefined && e.target != chk) {
            chk.checked = !chk.checked;
        }
    });
    jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = jQuery(e.target).attr("href");
        if (target = '#unlinked_items') {
            jQuery('.pagination_shop').DataTable().ajax.reload();
            jQuery('.pagination_b1').DataTable().ajax.reload();
        }
        if (target = '#linked_items') {
            jQuery('.pagination_linked').DataTable().ajax.reload();
        }
    });
</script>
<style>
    .dataTable {
        width: 100% !important;
    }
</style>