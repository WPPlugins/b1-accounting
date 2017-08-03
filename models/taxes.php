<?php

class ModelB1Taxes
{

    public static function getB1TaxRates()
    {
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "woocommerce_tax_rates` ORDER BY `tax_rate_id` ASC";
        return $wpdb->get_results($sql);
    }

    public static function getB1TaxRate($rate_id)
    {
        global $wpdb;
        $taxrate = $wpdb->get_results($wpdb->prepare("SELECT * FROM `" . $wpdb->prefix . "woocommerce_tax_rates` WHERE `tax_rate_id` = '%d'", intval(sanitize_text_field($rate_id))));
        if (isset($taxrate[0]->tax_rate)) {
            return $taxrate[0]->tax_rate;
        } else {
            return 0;
        }
    }

}
