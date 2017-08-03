<?php

class ModelB1Settings
{

    public static function getB1Setting($key)
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "b1_settings`  WHERE `key` = '%s'";
        $results = $wpdb->get_results($wpdb->prepare($sql, sanitize_text_field($key)));
        if (isset($results[0])) {
            return $results[0]->value;
        } else {
            return '';
        }
    }

    public static function setB1Setting($key, $value)
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE `" . $wpdb->prefix . "b1_settings` SET `value` = '%s' WHERE `key` = '%s'", sanitize_text_field($value), sanitize_text_field($key)));
    }

}
