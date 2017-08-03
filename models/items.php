<?php

class ModelB1Items
{

    public static function getB1UnlinkedItems($from, $items)
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "posts` LEFT JOIN  `" . $wpdb->prefix . "b1_linked_items` ON ID = shop_product_id  LEFT JOIN `" . $wpdb->prefix . "postmeta` ON ID =post_id WHERE `post_status` = 'publish' AND `shop_product_id` IS NULL AND ((`post_type` = 'product' AND `meta_key` = '_regular_price' AND `meta_value` != '') OR (`meta_key` LIKE %s AND `post_type` = 'product_variation')) ORDER BY `post_title` ASC LIMIT %d , %d";
        return $wpdb->get_results($wpdb->prepare($sql, 'attribute%', intval(sanitize_text_field($from)), intval(sanitize_text_field($items))));
    }

    public static function getB1UnlinkedItemsCount()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) as count FROM `" . $wpdb->prefix . "posts` LEFT JOIN  `" . $wpdb->prefix . "b1_linked_items` ON ID = shop_product_id  LEFT JOIN `" . $wpdb->prefix . "postmeta` ON ID =post_id WHERE `post_status` = 'publish' AND `shop_product_id` IS NULL AND ((`post_type` = 'product' AND `meta_key` = '_regular_price' AND `meta_value` != '') OR (`meta_key` LIKE 'attribute_%' AND `post_type` = 'product_variation'))";
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function getB1Items($from, $items, $relation)
    {
        global $wpdb;
        if ($relation == 'more_1') {
            $sql = "SELECT DISTINCT id, name, code FROM `" . $wpdb->prefix . "b1_items` LEFT JOIN `" . $wpdb->prefix . "b1_linked_items` ON id = b1_product_id ORDER BY `name` ASC LIMIT %d, %d";
        } else {
            $sql = "SELECT * FROM `" . $wpdb->prefix . "b1_items` LEFT JOIN `" . $wpdb->prefix . "b1_linked_items` ON id = b1_product_id WHERE `shop_product_id` IS NULL ORDER BY `name` ASC LIMIT %d, %d";
        }
        return $wpdb->get_results($wpdb->prepare($sql, intval(sanitize_text_field($from)), intval(sanitize_text_field($items))));
    }

    public static function getB1ItemsCountTable($relation)
    {
        global $wpdb;
        if ($relation == 'more_1') {
            $sql = "SELECT COUNT(*) as count FROM `" . $wpdb->prefix . "b1_items`";
        } else {
            $sql = "SELECT COUNT(*) as count FROM `" . $wpdb->prefix . "b1_items` LEFT JOIN `" . $wpdb->prefix . "b1_linked_items` ON id = b1_product_id WHERE `shop_product_id` IS NULL";
        }
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function getB1LinkedItems($from, $items)
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "b1_linked_items` ORDER BY `shop_product_name` ASC LIMIT %d, %d";
        return $wpdb->get_results($wpdb->prepare($sql, intval(sanitize_text_field($from)), intval(sanitize_text_field($items))));
    }

    public static function getB1LinkedItemsCount()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) as count FROM `" . $wpdb->prefix . "b1_linked_items` ";
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function unlinkB1Product($product_id)
    {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'b1_linked_items', array('shop_product_id' => intval(sanitize_text_field($product_id))), array('%s'));
    }

    public static function getB1ShopItemsCount()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) as count FROM `" . $wpdb->prefix . "posts`  WHERE `post_type` = 'product' AND `post_status` = 'publish'";
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function getB1ItemsCount()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) as count FROM " . $wpdb->prefix . "b1_items";
        return $wpdb->get_results($sql)[0]->count;
    }

    public static function linkB1Product($shop_id, $b1_id)
    {
        global $wpdb;
        if ((intval(sanitize_text_field($shop_id)) != 0) && (intval(sanitize_text_field($b1_id)) != 0)) {
            $product = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "posts` WHERE `ID` = '%d'", intval(sanitize_text_field($shop_id))))[0]->post_title;
            $b1_product = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "b1_items` WHERE `id` = '%d'", intval(sanitize_text_field($b1_id))));
            $wpdb->insert(
                $wpdb->prefix . 'b1_linked_items', array(
                'shop_product_id' => intval(sanitize_text_field($shop_id)),
                'b1_product_id' => intval(sanitize_text_field($b1_id)),
                'shop_product_name' => $product,
                'b1_product_name' => $b1_product[0]->name,
                'b1_product_code' => $b1_product[0]->code,
            ), array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                )
            );
        }
    }

    public static function resetB1Items()
    {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `" . $wpdb->prefix . "b1_linked_items`");
    }

    public static function addB1Item($name, $code, $id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("INSERT IGNORE INTO " . $wpdb->prefix . "b1_items SET `name` = '%s', `code` = '%s', `id` = '%d'", sanitize_text_field($name), sanitize_text_field($code), intval(sanitize_text_field($id))));
    }

    public static function getB1Item($id)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "b1_linked_items` WHERE `b1_product_id` = '%d'", intval(sanitize_text_field($id))));
    }

    public static function quantityB1Update($quantity, $item_id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE `" . $wpdb->prefix . "postmeta` SET `meta_value` = '%s' WHERE `meta_key` = '_stock' AND `post_id` = '%d'", intval(sanitize_text_field($quantity)), intval(sanitize_text_field($item_id))));
    }

    public static function quantityB1UpdateOnFetch($quantity, $item_id)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE `" . $wpdb->prefix . "b1_linked_items` 
            LEFT JOIN `" . $wpdb->prefix . "posts` ON ID = shop_product_id
            LEFT JOIN `" . $wpdb->prefix . "postmeta` ON post_id = ID
            SET `meta_value` = '%d'
            WHERE `post_type` = 'product' AND `post_status` = 'publish' AND `meta_key` = '_stock' AND `b1_product_id` = '%d'", intval(sanitize_text_field($quantity)), intval(sanitize_text_field($item_id))));
    }

    public static function deleteB1LinkedItems($b1_ids)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM  " . $wpdb->prefix . "b1_linked_items WHERE b1_product_id NOT IN (" . implode(',', $b1_ids) . ")");
    }

    public static function deleteB1Items($b1_ids)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM  " . $wpdb->prefix . "b1_items WHERE id NOT IN (" . implode(',', $b1_ids) . ")");
    }

    public static function getB1LinkedItem($id)
    {
        global $wpdb;
        $query = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "b1_linked_items`  WHERE `shop_product_id` = '%d'", intval(sanitize_text_field($id))));
        if (count($query) > 0) {
            return $query[0]->b1_product_id;
        } else {
            return false;
        }
    }

}
