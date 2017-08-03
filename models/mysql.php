<?php

function generateB1CronKey()
{
    return hash_hmac('sha256', uniqid(rand(), true), microtime() . rand());
}

class ModelB1Mysql
{

    public static function createB1Table()
    {
        global $wpdb;
        $wpdb->show_errors();
        $settings_db = $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "b1_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `key` varchar(128) NOT NULL,
            `value` varchar(128) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

        if ($settings_db === false) {
            $wpdb->print_error();
            die();
        }
        $items_db = $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "b1_items` (`id` INT(11) NOT NULL,
            `date_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `name` TEXT,
            `code` TEXT,
            `quantity` INT(11) DEFAULT NULL,
            `is_product` TINYINT(1) DEFAULT NULL,
            `is_ghost` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `id` (`id`)
        ) ENGINE=MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
        if ($items_db === false) {
            $wpdb->print_error();
            die();
        }
        $orders_db = $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "b1_orders` (
            `b1_order_id` INT(11) DEFAULT NULL,
            `shop_order_id` INT(11),
            `b1_sync_count` int(11) NOT NULL DEFAULT '0',
            `b1_sync_id` INT(11) DEFAULT NULL,
            `next_sync` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`shop_order_id`),
            UNIQUE KEY `id` (`shop_order_id`)
        ) ENGINE=MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
        if ($orders_db === false) {
            $wpdb->print_error();
            die();
        }

        $linked_db = $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "b1_linked_items` (
            `shop_product_id` int(11) NOT NULL,
            `b1_product_id` int(11) NOT NULL,
            `shop_product_name` text NOT NULL,
            `b1_product_name` text NOT NULL,
            `b1_product_code` text NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
        if ($linked_db === false) {
            $wpdb->print_error();
            die();
        }
    }

    function deleteB1Table()
    {
        global $wpdb;
        $wpdb->show_errors();
        $settings_db = $wpdb->query("DROP TABLE `" . $wpdb->prefix . "b1_items`, `" . $wpdb->prefix . "b1_orders`, `" . $wpdb->prefix . "b1_settings`, `" . $wpdb->prefix . "b1_linked_items`;");
        if ($settings_db === false) {
            $wpdb->print_error();
            die();
        }
    }

    public static function fillB1DefaultDb()
    {
        global $wpdb;
        $wpdb->show_errors();
        $fill_settings = $wpdb->query("INSERT IGNORE INTO `" . $wpdb->prefix . "b1_settings` (`id`, `date_added`, `key`, `value`) VALUES 
            ('1', CURRENT_TIMESTAMP, 'cron_key', '" . generateB1CronKey() . "'), 
            ('2', CURRENT_TIMESTAMP, 'api_key', ''),
            ('3', CURRENT_TIMESTAMP, 'private_key', ''),
            ('4', CURRENT_TIMESTAMP, 'shop_id', '" . base_convert(time(), 10, 36) . "'),
            ('5', CURRENT_TIMESTAMP, 'b1_id', '" . base_convert(time(), 10, 36) . "'),
            ('6', CURRENT_TIMESTAMP, 'documentation_url', 'https://www.b1.lt/doc/api'),
            ('7', CURRENT_TIMESTAMP, 'b1_contact_email', 'info@b1.lt'),
            ('8', CURRENT_TIMESTAMP, 'order_max_sync_count', '10'),
            ('9', CURRENT_TIMESTAMP, 'fetch_items_per_sync_iteration', '100'),
            ('10', CURRENT_TIMESTAMP, 'max_requests_to_api', '100'),
            ('11', CURRENT_TIMESTAMP, 'max_items_per_request', '100'),
            ('12', CURRENT_TIMESTAMP, 'display_sync_orders_without_writeoff_button', '1'),
            ('13', CURRENT_TIMESTAMP, 'help_page_url', 'https://www.b1.lt/help'),
            ('14', CURRENT_TIMESTAMP, 'orders_sync_from', '" . date('Y-m-d') . "'),
            ('15', CURRENT_TIMESTAMP, 'tax_rate_id', ''),
            ('16', CURRENT_TIMESTAMP, 'relation_type', '1_1'),
            ('17', CURRENT_TIMESTAMP, 'initial_sync', '0'),
            ('18', CURRENT_TIMESTAMP, 'products_sync_count', '0'),
            ('19', CURRENT_TIMESTAMP, 'products_next_sync', ''),
            ('20', CURRENT_TIMESTAMP, 'quantities_sync_count', '0'),
            ('21', CURRENT_TIMESTAMP, 'quantities_next_sync', '')
        ;");
        if ($fill_settings === false) {
            $wpdb->print_error();
            die();
        }
    }

}
