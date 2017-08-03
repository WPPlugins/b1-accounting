<?php

class ModelB1Orders
{

    public static function resetB1OrdersSync()
    {
        global $wpdb;
        $wpdb->query("UPDATE `" . $wpdb->prefix . "b1_orders` SET `b1_sync_id` = NULL WHERE b1_sync_id = 0");
    }

    public static function resetNotSyncB1Orders()
    {
        global $wpdb;
        $wpdb->query("UPDATE `" . $wpdb->prefix . "b1_orders` SET `next_sync` = NULL, `b1_sync_count` = 0  WHERE next_sync < '" . date('Y-m-d H:i:s') . "'");
    }

    public static function getB1Order($order_id)
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "posts`  WHERE `post_type` = 'shop_order' AND `ID` = '%d'";
        return $wpdb->get_results($wpdb->prepare($sql, intval(sanitize_text_field($order_id))));
    }

    public static function getFailedB1Orders()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) as count FROM " . $wpdb->prefix . "b1_orders WHERE b1_sync_id IS NOT NULL";
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function hideB1OrdersFromSyncByDate($date)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "b1_orders LEFT JOIN " . $wpdb->prefix . "posts ON ID = shop_order_id SET `b1_order_id` = 0 WHERE `post_type` = 'shop_order' AND `post_status` = 'wc-completed' AND `b1_order_id` IS NULL AND `post_date` < '%s'", sanitize_text_field($date)));
    }

    public static function setB1SyncId($id, $ttl, $thresold)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "b1_orders SET `b1_sync_id` = %d WHERE ((b1_order_id IS NULL AND b1_sync_id IS NULL) OR (%d - b1_sync_id > %d AND b1_sync_count < %d)) AND (next_sync < '%s' OR next_sync IS NULL)", $id, $id, $ttl, $thresold, date('Y-m-d H:i:s')));
    }

    public static function getSyncB1Orders($orders_sync_from, $id, $iteration, $thresold)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "posts LEFT JOIN " . $wpdb->prefix . "b1_orders ON ID = shop_order_id  WHERE (`post_type` = 'shop_order' AND `post_status` = 'wc-completed' AND `post_date` >= '%s') AND (b1_sync_id = %d OR b1_order_id IS NULL) AND (b1_sync_count < %d || b1_order_id IS NULL) ORDER BY post_date LIMIT %d", sanitize_text_field($orders_sync_from), $id, $thresold, $iteration));
    }

    public static function addB1Order($shop_order_id, $b1_order_id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("INSERT INTO `" . $wpdb->prefix . "b1_orders` (`b1_order_id`, `shop_order_id`, `b1_sync_count` , `b1_sync_id` ) VALUES (NULL, '%d', 0,  '%d')", $shop_order_id, $b1_order_id));
    }

    public static function setB1OrderReference($b1_order_id, $shop_order_id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE `" . $wpdb->prefix . "b1_orders` SET `b1_sync_id` = NULL , `b1_order_id` = '%d' WHERE `shop_order_id` = '%d'", intval(sanitize_text_field($b1_order_id)), intval(sanitize_text_field($shop_order_id))));
    }

    public static function setB1FailedSync($order)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE `" . $wpdb->prefix . "b1_orders` SET `b1_sync_id` = NULL, `b1_sync_count` = b1_sync_count + 1, `next_sync` = IF(b1_sync_count >= '10', '" . date('Y-m-d H:i:s', strtotime('6 hour')) . "' , NULL)  WHERE `shop_order_id` = '%d'", intval(sanitize_text_field($order))));
    }

    public static function unsetB1SyncOrders($id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "b1_orders SET `b1_sync_id` = NULL WHERE `b1_sync_id` = '%d'", $id));
    }

    public static function getB1OrderProducts($id)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "woocommerce_order_items`  WHERE `order_id` = '%d'", intval(sanitize_text_field($id))));
    }

    public static function resetB1Orders()
    {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `" . $wpdb->prefix . "b1_orders`");
        $wpdb->query("UPDATE `" . $wpdb->prefix . "b1_settings` SET `value` = '0' WHERE `key` = 'initial_sync'");
    }

    public static function getB1OrderItemMeta($order, $key)
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta`  WHERE `meta_key` = '%s' AND `order_item_id` = '%d'";
        $results = $wpdb->get_results($wpdb->prepare($sql, sanitize_text_field($key), sanitize_text_field($order)));
        if (isset($results[0])) {
            return $results[0]->meta_value;
        } else {
            return '';
        }
    }

}
