<?php

/*
  Plugin Name: B1.lt
  Plugin URI:  https://www.b1.lt
  Description: Plugin for B1 accouting to sync products and orders
  Version:     1.1.8
  Author:      B1.lt
  Author URI:  https://b1.lt
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Text Domain: wporg
  Domain Path: /languages
 */
defined('ABSPATH') or die("No script kiddies please!");

require_once plugin_dir_path(__FILE__) . 'models/mysql.php';
require_once plugin_dir_path(__FILE__) . 'models/items.php';
require_once plugin_dir_path(__FILE__) . 'models/settings.php';
require_once plugin_dir_path(__FILE__) . 'models/orders.php';
require_once plugin_dir_path(__FILE__) . 'models/taxes.php';
require_once plugin_dir_path(__FILE__) . 'models/helper.php';
require_once plugin_dir_path(__FILE__) . 'lib/B1.php';
/* Plugin activation */
register_activation_hook(__FILE__, array('ModelB1Mysql', 'createB1Table'));
register_activation_hook(__FILE__, array('ModelB1Mysql', 'fillB1DefaultDb'));
register_activation_hook(__FILE__, 'b1Cron');
/* Plugin deactivation */
register_uninstall_hook(__FILE__, array('ModelB1Mysql', 'deleteB1Table'));

add_action('admin_init', 'wp_verify_nonce');
function add_b1_cron_intervals($schedules)
{
    $schedules['productsSyncInterval'] = array(
        'interval' => 60 * 60 * 12,
        'display' => __('Every 12 hours')
    );

    $schedules['quantitiesSyncInterval'] = array(
        'interval' => 60 * 60 * 4,
        'display' => __('Every 4 hours')
    );

    $schedules['ordersSyncInterval'] = array(
        'interval' => 60 * 5,
        'display' => __('Every 5 minute')
    );

    return $schedules;
}

add_filter('cron_schedules', 'add_b1_cron_intervals');

function b1Cron()
{
    if (!wp_next_scheduled('b1_cron_products_event')) {
        wp_schedule_event(current_time('timestamp'), 'productsSyncInterval', 'b1_cron_products_event');
    }
    if (!wp_next_scheduled('b1_cron_quantities_event')) {
        wp_schedule_event(current_time('timestamp'), 'quantitiesSyncInterval', 'b1_cron_quantities_event');
    }
    if (!wp_next_scheduled('b1_cron_orders_event')) {
        wp_schedule_event(current_time('timestamp'), 'ordersSyncInterval', 'b1_cron_orders_event');
    }
}

add_action('b1_cron_products_event', 'do_b1_cron_products_event');
add_action('b1_cron_quantities_event', 'do_b1_cron_quantities_event');
add_action('b1_cron_orders_event', 'do_b1_cron_orders_event');

function do_b1_cron_products_event()
{
    require_once plugin_dir_path(__FILE__) . 'cron.php';
    new B1Cron('products');
}

function do_b1_cron_quantities_event()
{
    require_once plugin_dir_path(__FILE__) . 'cron.php';
    new B1Cron('quantities');
}

function do_b1_cron_orders_event()
{
    require_once plugin_dir_path(__FILE__) . 'cron.php';
    new B1Cron('orders');
}

register_deactivation_hook(__FILE__, 'b1_cron_deactivation');

function b1_cron_deactivation()
{
    wp_clear_scheduled_hook('b1_cron_products_event');
    wp_clear_scheduled_hook('b1_cron_quantities_event');
    wp_clear_scheduled_hook('b1_cron_orders_event');
}

if (isset($_GET['cron']) && isset($_GET['key'])) {

    require_once plugin_dir_path(__FILE__) . 'cron.php';
    $cronType = sanitize_text_field($_GET['cron']);
    $key = sanitize_text_field($_GET['key']);
    if ($cronType == 'orders' || $cronType == 'products' || $cronType == 'quantities') {
        if (!class_exists('B1')) {
            require_once plugin_dir_path(__FILE__) . 'lib/B1.php';
        }
        if (class_exists('B1Cron')) {
            new B1Cron($cronType, $key);
        }
    }
    if ($cronType == 'invoice' && isset($_GET['order'])) {
        $order = sanitize_text_field($_GET['oder']);
        new B1Cron($cronType, $key, $order);
    }
    exit();
}
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_GET['draw'])) {
    add_action('init', 'b1_ajaxCall');
}
function b1_ajaxCall()
{
    $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : null;

    $pagination_data = array();
    $pagination_data['draw'] = intval(sanitize_text_field($_GET['draw']));
    $pagination_data['type'] = sanitize_text_field($_GET['type']);
    $pagination_data['start'] = intval(sanitize_text_field($_GET['start']));
    $pagination_data['length'] = intval(sanitize_text_field($_GET['length']));
    $type = intval(sanitize_text_field($_GET['type']));
    if (wp_verify_nonce($nonce, 'cron')) {
        if ($pagination_data['type'] == 'pagination_shop') {
            $ajax_sorting_data = ModelB1Items::getB1UnlinkedItems($pagination_data['start'], $pagination_data['length']);
            $total = ModelB1Items::getB1UnlinkedItemsCount();
            foreach ($ajax_sorting_data as $item) {
                if ($item->post_type == 'product') {
                    $pagination_data['data'][] = array('name' => $item->post_title, 'id' => $item->ID);
                } else {
                    $pagination_data['data'][] = array('name' => $item->post_title . ' | ' . $item->meta_value, 'id' => $item->ID);
                }
            }
        }
        if ($pagination_data['type'] == 'pagination_b1') {
            $ajax_sorting_data = ModelB1Items::getB1Items($pagination_data['start'], $pagination_data['length'], ModelB1Settings::getB1Setting('relation_type'));
            $total = ModelB1Items::getB1ItemsCountTable(ModelB1Settings::getB1Setting('relation_type'));
            foreach ($ajax_sorting_data as $item) {
                $pagination_data['data'][] = array('name' => $item->name, 'id' => $item->id, 'code' => $item->code);
            }
        }
        if ($pagination_data['type'] == 'pagination_linked') {
            $ajax_sorting_data = ModelB1Items::getB1LinkedItems($pagination_data['start'], $pagination_data['length']);
            $total = ModelB1Items::getB1LinkedItemsCount();
            foreach ($ajax_sorting_data as $item) {
                $pagination_data['data'][] = array(
                    'name' => $item->shop_product_name,
                    'b1_name' => $item->b1_product_name,
                    'id' => $item->shop_product_id,
                    'b1_reference_id' => $item->b1_product_id,
                    'upc' => $item->b1_product_code
                );
            }
        }
    } else {
        $total = 0;
    }
    $pagination_data['recordsTotal'] = $total;
    $pagination_data['recordsFiltered'] = $total;

    if ($pagination_data['recordsTotal'] == 0) {
        $pagination_data['data'] = array();
    }
    echo json_encode($pagination_data);
    exit();
}

/* B1 menu at WooCommerce submenu creation */
add_action('admin_menu', 'register_b1_submenu_page');

function register_b1_submenu_page()
{
    add_submenu_page('woocommerce', 'B1', 'B1', 'manage_options', 'b1-submenu-page', 'b1_submenu_page_callback');
}

function b1_invoice()
{
    $array = explode('/', $_SERVER['REQUEST_URI']);
    array_pop($array);
    $order_id = end($array);
    $orders = ModelB1Orders::getB1Order(intval(sanitize_text_field($order_id)));
    if (count($orders) > 0) {
        set_query_var(admin_url() . 'admin.php?page=b1-submenu-page&cron=invoice&order=' . $order_id . '&key=' . $orders[0]->post_password);
    } else {
        set_query_var('b1_accounting_link', '');
    }
}

add_action('template_redirect', 'b1_invoice');

function b1_submenu_page_callback()
{
    $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : null;
    /* Settings update */
    if (wp_verify_nonce($nonce, 'settings')) {
        if (isset($_POST["b1_id"])) {
            $old_sync_from = ModelB1Settings::getB1Setting('orders_sync_from');
            ModelB1Settings::setB1Setting('b1_id', sanitize_text_field($_POST['b1_id']));
            ModelB1Settings::setB1Setting('api_key', sanitize_text_field($_POST['api_key']));
            ModelB1Settings::setB1Setting('shop_id', sanitize_text_field($_POST['shop_id']));
            ModelB1Settings::setB1Setting('private_key', sanitize_text_field($_POST['private_key']));
            ModelB1Settings::setB1Setting('cron_key', sanitize_text_field($_POST['cron_key']));
            ModelB1Settings::setB1Setting('orders_sync_from', sanitize_text_field($_POST['orders_sync_from']));
            ModelB1Settings::setB1Setting('tax_rate_id', intval(sanitize_text_field($_POST['tax_rate_id'])));
            ModelB1Settings::setB1Setting('relation_type', sanitize_text_field($_POST['relation_type']));
            if (ModelB1Settings::getB1Setting('orders_sync_from') != $old_sync_from) {
                ModelB1Orders::resetB1OrdersSync();
            }
        }
    }
    /* Settings tab data */
    $settings_b1_id = ModelB1Settings::getB1Setting('b1_id');
    $settings_shop_id = ModelB1Settings::getB1Setting('shop_id');
    $settings_api_key = ModelB1Settings::getB1Setting('api_key');
    $settings_private_key = ModelB1Settings::getB1Setting('private_key');
    $settings_cron_key = ModelB1Settings::getB1Setting('cron_key');
    $settings_contact_email = ModelB1Settings::getB1Setting('b1_contact_email');
    $settings_documentation_url = ModelB1Settings::getB1Setting('documentation_url');
    $settings_help_page_url = ModelB1Settings::getB1Setting('help_page_url');
    $settings_orders_sync_from = ModelB1Settings::getB1Setting('orders_sync_from');
    $settings_tax_rate_id = ModelB1Settings::getB1Setting('tax_rate_id');
    $settings_relation_type = ModelB1Settings::getB1Setting('relation_type');
    $datatables_translation = '"language": {
            "sProcessing":   "Apdorojama...",
            "sLengthMenu":   "Rodyti _MENU_ įrašus",
            "sZeroRecords":  "Įrašų nerasta",
            "sInfo":         "Rodomi įrašai nuo _START_ iki _END_ iš _TOTAL_ įrašų",
            "sInfoEmpty":    "Rodomi įrašai nuo 0 iki 0 iš 0",
            "oPaginate": {
                "sFirst":    "Pirmas",
                "sPrevious": "Ankstesnis",
                "sNext":     "Tolimesnis",
                "sLast":     "Paskutinis"
            }
        },';

    $path = admin_url() . 'admin.php?page=b1-submenu-page&cron=';
    $cron_urls = array(
        'fetchItemsFromB1' => array(
            'url' => $path . 'products&key=' . $settings_cron_key,
        ),
        'fetchQuantities' => array(
            'url' => $path . 'quantities&key=' . $settings_cron_key,
        ),
        'syncOrdersWithB1' => array(
            'url' => $path . 'orders&key=' . $settings_cron_key,
        ),
    );
    if (isset($_POST['form']) && wp_verify_nonce($nonce, 'settings')) {
        if ($_POST['form'] == 'link_product') {
            if (is_array($_POST['shop_item'])) {
                foreach ($_POST['shop_item'] as $item) {
                    ModelB1Items::linkB1Product(intval(sanitize_text_field($item)), intval(sanitize_text_field($_POST['b1_item'])));
                }
            } else {
                ModelB1Items::linkB1Product(intval(sanitize_text_field($_POST['shop_item'])), intval(sanitize_text_field($_POST['b1_item'])));
            }
        }
        if ($_POST['form'] == 'unlink_product') {
            ModelB1Items::unlinkB1Product(intval(sanitize_text_field($_POST['shop_item'])));
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == 'reset_all' && wp_verify_nonce($nonce, 'settings')) {
        ModelB1Items::resetB1Items();
        header("Location: " . admin_url() . 'admin.php?page=b1-submenu-page');
    }
    if (isset($_GET['action']) && $_GET['action'] == 'reset_all_orders' && wp_verify_nonce($nonce, 'settings')) {
        ModelB1Orders::resetB1Orders();
        header("Location: " . admin_url() . 'admin.php?page=b1-submenu-page');
    }

    $b1_stat_items_eshop = ModelB1Items::getB1ShopItemsCount();
    $b1_stat_items_b1 = ModelB1Items::getB1ItemsCount();
    $b1_stat_failed_orders = ModelB1Orders::getFailedB1Orders();
    $tax_rates = ModelB1Taxes::getB1TaxRates();

    require_once plugin_dir_path(__FILE__) . 'languages/lt/lt_loc.php';
    require_once plugin_dir_path(__FILE__) . 'view/dashboard.php';

}
