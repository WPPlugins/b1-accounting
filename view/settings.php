<?php defined('ABSPATH') or die('No script kiddies please!'); ?>
<div class="row">
    <div class="col-sm-12 col-md-12 col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-cogs"></i> <?php echo $b1_localization_strings['text_configuration']; ?>
                </h3>
            </div>
            <div class="panel-body">
                <form action="" method="post" id="form-b1-settings" class="form-horizontal">
                    <?= wp_nonce_field('settings') ?>
                    <div class="alert alert-success hidden" id="success-msg" role="alert">
                        <button type="button" class="close"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-check"></i> <?php echo $b1_localization_strings['text_settings_save_success']; ?>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="b1_id">ID (B1):</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="b1_id" value="<?php echo $settings_b1_id; ?>" placeholder="ID (B1)" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="shop_id">Eshop ID:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="shop_id" value="<?php echo $settings_shop_id; ?>" placeholder="Eshop ID" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="shop_id">Orders syncfrom:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="orders_sync_from" value="<?php echo $settings_orders_sync_from; ?>" placeholder="Orders sync from" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="shop_id">Items relations forlink:</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="relation_type">
                                <option value="1_1" <?php if ($settings_relation_type == '1_1') {
                                    echo 'selected';
                                } ?>>One to one
                                </option>
                                <option value="more_1" <?php if ($settings_relation_type == 'more_1') {
                                    echo 'selected';
                                } ?>>More to one
                                </option>
                            </select>
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="shop_id">VAT Tax rate ID:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="tax_rate_id" value="<?php echo $settings_tax_rate_id; ?>" placeholder="If shop don't have VAT leave empty" type="text">
                            <div class="error-message"></div>
                            <br>
                            <div class="alert alert-info" role="alert">
                                <?php foreach ($tax_rates as $item) { ?>
                                    <div><?= 'ID ' . $item->tax_rate_id . ' -  ' . $item->tax_rate_name . ' ' . $item->tax_rate ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="api_key">API key:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="api_key" value="<?php echo $settings_api_key; ?>" placeholder="API key" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="private_key">Private key:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="private_key" value="<?php echo $settings_private_key; ?>" placeholder="Private key" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="cron_key">CRON key:</label>
                        <div class="col-sm-9">
                            <input class="form-control" name="cron_key" value="<?php echo $settings_cron_key; ?>" placeholder="CRON key" type="text">
                            <div class="error-message"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo $b1_localization_strings['button_update'] ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-12 col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-dashboard"></i> <?php echo $b1_localization_strings['stats']; ?>
                </h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-8">
                        <?php echo $b1_localization_strings['stats_eshop_items']; ?>
                    </div>
                    <div class="col-sm-4 text-right">
                        <span class="label label-info" id="items-count-eshop"><?= $b1_stat_items_eshop ?></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-8">
                        <?php echo $b1_localization_strings['stats_b1_items']; ?>
                    </div>
                    <div class="col-sm-4 text-right">
                        <span class="label label-primary" id="items-count-b1"><?= $b1_stat_items_b1 ?></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-8">
                        <?php echo $b1_localization_strings['stats_orders']; ?>
                    </div>
                    <div class="col-sm-4 text-right">
                        <span class="label label-danger" id="items-count-order_fail"><?= $b1_stat_failed_orders ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-repeat"></i> <?php echo $b1_localization_strings['text_cron']; ?></h3>
            </div>
            <div class="list-group">
                <?php foreach ($cron_urls as $name => $data) { ?>
                    <div class="list-group-item">
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo $data['url'] ?>">
                            <div class="input-group-btn">
                                <a href="<?php echo $data['url'] ?>" type="button " target="_blank" class="btn btn-primary">
                                    <i class="fa fa-play-circle" aria-hidden="true"></i> <?php echo $b1_localization_strings['run_cron']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <a href="admin.php?page=b1-submenu-page&action=reset_all&_wpnonce=<?= wp_create_nonce('settings'); ?>" onclick="return reset_all()" class="btn btn-default"><?php echo $b1_localization_strings['reset_all']; ?></a>
        <a href="admin.php?page=b1-submenu-page&action=reset_all_orders&_wpnonce=<?= wp_create_nonce('settings'); ?>" onclick="return reset_all()" class="btn btn-default"><?php echo $b1_localization_strings['reset_all_orders']; ?></a>
    </div>
</div>