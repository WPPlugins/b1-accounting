<?php

class B1CronException extends Exception
{

    private $extraData;

    public function __construct($message = "", $extraData = array(), $code = 0, \Exception $previous = null)
    {
        $this->extraData = $extraData;
        parent::__construct($message, $code, $previous);
    }

    public function getExtraData()
    {
        return $this->extraData;
    }

}

class B1Cron
{

    const TTL = 3600;
    const MAX_ITERATIONS = 300;
    const ORDERS_PER_ITERATION = 300;
    const ORDER_SYNC_THRESHOLD = 10;

    private $get_id;
    private $get_key;
    private $get_order;
    private $settings = array();

    /**
     * @var $wpdb
     */
    private $db;

    private function init()
    {
        set_time_limit(self::TTL);
        ini_set('max_execution_time', self::TTL);

        global $wpdb;
        $this->db = $wpdb;
        $this->settings['api_key'] = ModelB1Settings::getB1Setting('api_key');
        $this->settings['private_key'] = ModelB1Settings::getB1Setting('private_key');
        $this->settings['cron_key'] = ModelB1Settings::getB1Setting('cron_key');
    }

    private function validateAccess()
    {
        if ($this->get_id != 'invoice') {
            if ($this->settings['cron_key'] == null) {
                throw new B1CronException('Fatal error.');
            }
            if ($this->get_key != null && $this->settings['cron_key'] != $this->get_key) {
                exit();
            }
        }
    }

    public function __construct($method = 'products', $key = null, $order = null)
    {
        $this->get_id = $method;
        $this->get_key = $key;
        $this->get_order = $order;
        $this->init();
        switch ($this->get_id) {
            case 'products':
                $this->validateAccess();
                $this->fetchProducts();
                break;
            case 'quantities':
                $this->validateAccess();
                $this->fetchQuantities();
                break;
            case 'orders':
                $this->validateAccess();
                $this->syncOrders();
                break;
            case 'invoice':
                $this->getInvoice($this->get_order, $this->get_key);
                break;
            default:
                throw new B1CronException('Bad action ID specified.');
        }
    }

    private function fetchProducts()
    {
        $b1 = new B1(['apiKey' => $this->settings['api_key'], 'privateKey' => $this->settings['private_key']]);
        $i = 0;
        $lid = 0;
        $b1_ids = array();
        if (ModelB1Settings::getB1Setting('products_next_sync') < date('Y-m-d H:i:s') || ModelB1Settings::getB1Setting('products_next_sync') == '') {
            if (ModelB1Settings::getB1Setting('products_next_sync') != '') {
                ModelB1Settings::setB1Setting('products_next_sync', '');
                ModelB1Settings::setB1Setting('products_sync_count', 0);
            }
            do {
                $productsSyncCount = ModelB1Settings::getB1Setting('products_sync_count');
                ob_start();
                $i++;
                try {
                    $data = $b1->exec('shop/product/list', array("lid" => $lid));
                    if ($data != false) {
                        foreach ($data['data'] as $item) {
                            $b1_ids[] = intval(sanitize_text_field($item['id']));
                            ModelB1Items::addB1Item($item['name'], $item['code'], $item['id']);
                            if (ModelB1Settings::getB1Setting('orders_sync_from') == '1_1') {
                                if ($item['quantity']) {
                                    $post_data = ModelB1Items::getB1Item($item['id']);
                                    ModelB1Items::quantityB1Update($item['quantity'], $post_data[0]->shop_product_id);
                                }
                            }
                        }
                        if (count($data['data']) == 100) {
                            $lid = $data['data'][99]['id'];
                        } else {
                            $lid = -1;
                        }
                    } else {
                        ModelB1Settings::setB1Setting('products_sync_count', $productsSyncCount + 1);
                        throw new B1CronException('Error getting data from B1.lt');
                    }
                } catch (B1Exception $e) {
                    ModelB1Settings::setB1Setting('products_sync_count', $productsSyncCount + 1);
                    ModelB1Helper::printPreB1($e->getMessage());
                    ModelB1Helper::printPreB1($e->getExtraData());
                }
                echo "$i;";
                ob_end_flush();
            } while ($lid != -1 && $i < self::MAX_ITERATIONS && $productsSyncCount < 9);

            if (ModelB1Settings::getB1Setting('products_sync_count') >= 10) {
                ModelB1Settings::setB1Setting('products_next_sync', date('Y-m-d H:i:s', strtotime('6 hour')));
            }

            if (!empty($b1_ids)) {
                ModelB1Items::deleteB1LinkedItems($b1_ids);
                ModelB1Items::deleteB1Items($b1_ids);
            }
        }
        echo 'OK';
    }

    private function fetchQuantities()
    {
        $b1 = new B1(['apiKey' => $this->settings['api_key'], 'privateKey' => $this->settings['private_key']]);
        if (ModelB1Settings::getB1Setting('orders_sync_from') == '1_1') {
            echo 'OK';
            exit();
        }
        $i = 0;
        $lid = 0;
        $b1_ids = array();

        if (ModelB1Settings::getB1Setting('quantities_sync_count') >= 10 && ModelB1Settings::getB1Setting('quantities_next_sync') == '') {
            ModelB1Settings::setB1Setting('quantities_next_sync', date('Y-m-d H:i:s', strtotime('6 hour')));
        }

        if (ModelB1Settings::getB1Setting('quantities_next_sync') < date('Y-m-d H:i:s') || ModelB1Settings::getB1Setting('quantities_next_sync') == '') {
            if (ModelB1Settings::getB1Setting('quantities_next_sync') != '') {
                ModelB1Settings::setB1Setting('quantities_next_sync', '');
                ModelB1Settings::setB1Setting('quantities_sync_count', 0);
            }
            do {
                $quantitiesSyncCount = ModelB1Settings::getB1Setting('quantities_sync_count');
                ob_start();
                $i++;
                try {
                    $data = $b1->exec('shop/product/quantity/list', array("lid" => $lid));
                    if ($data != false) {
                        foreach ($data['data'] as $item) {
                            $b1_ids[] = intval(sanitize_text_field($item['id']));
                            if ($item['quantity']) {
                                ModelB1Items::quantityB1UpdateOnFetch($item['quantity'], $item['id']);
                            }
                        }
                        if (count($data['data']) == 100) {
                            $lid = $data['data'][99]['id'];
                        } else {
                            $lid = -1;
                        }
                    } else {
                        ModelB1Settings::setB1Setting('quantities_sync_count', $quantitiesSyncCount + 1);
                        throw new B1CronException('Error getting data from B1.lt');
                    }
                } catch (B1Exception $e) {
                    ModelB1Settings::setB1Setting('quantities_sync_count', $quantitiesSyncCount + 1);
                    ModelB1Helper::printPreB1($e->getMessage());
                    ModelB1Helper::printPreB1($e->getExtraData());
                    throw new B1CronException('Error syncing order #' . intval(sanitize_text_field($item->ID)) . ' with B1.lt');
                }
                echo "$i;";
                ob_end_flush();
            } while ($lid != -1 && $i < self::MAX_ITERATIONS && $quantitiesSyncCount < 9);

            if (!empty($b1_ids)) {
                ModelB1Items::deleteB1LinkedItems($b1_ids);
            }
        }

        echo 'OK';
    }

    private function syncOrders()
    {
        $b1 = new B1(['apiKey' => $this->settings['api_key'], 'privateKey' => $this->settings['private_key']]);
        $id = time();
        try {
            $orders_sync_from = ModelB1Settings::getB1Setting('orders_sync_from');
            if (!$orders_sync_from) {
                throw new B1CronException('Not set orders_sync_from value');
            }
            $data_prefix = ModelB1Settings::getB1Setting('shop_id');
            if (!$data_prefix) {
                throw new B1CronException('Not set shop_id value');
            }
            $initial_sync = ModelB1Settings::getB1Setting('initial_sync');
            if (!$initial_sync & $initial_sync != 0) {
                throw new B1CronException('Not set initial_sync value');
            }
            $tax_rate_id = ModelB1Settings::getB1Setting('tax_rate_id');
            if ($tax_rate_id == '') {
                $tax_rate = null;
            } else {
                $tax_rate = ModelB1Taxes::getB1TaxRate($tax_rate_id);
            }
            ModelB1Orders::resetNotSyncB1Orders();
            ModelB1Orders::hideB1OrdersFromSyncByDate($orders_sync_from);
            ModelB1Orders::setB1SyncId($id, self::TTL, self::ORDER_SYNC_THRESHOLD);
            $i = 0;
            do {
                ob_start();
                $i++;
                $orders = ModelB1Orders::getSyncB1Orders($orders_sync_from, $id, self::ORDERS_PER_ITERATION, self::ORDER_SYNC_THRESHOLD);
                $processed = 0;
                foreach ($orders as $item) {
                    if ($item->b1_sync_id == null) {
                        ModelB1Orders::addB1Order($item->ID, $id);
                    }
                    $order_data = $this->generateOrderData($item, $data_prefix, $initial_sync, $tax_rate);
                    try {
                        $request = $b1->exec('shop/order/add', $order_data);
                        if ($request != false) {
                            ModelB1Orders::setB1OrderReference($request['data']['orderId'], $item->ID);
                        } else {
                            ModelB1Orders::setB1FailedSync($item->ID);
                            throw new B1CronException('Error getting data from B1.lt');
                        }
                    } catch (B1DuplicateException $e) {
                        ModelB1Orders::setB1FailedSync($item->ID);
                        $receivedData = json_decode($e->getExtraData()['received']['body']);
                        ModelB1Orders::setB1OrderReference($receivedData->data->orderId, $item->ID);
                        ModelB1Helper::printPreB1($e->getMessage());
                        ModelB1Helper::printPreB1($e->getExtraData());
                        throw new B1CronException('Error syncing order #' . intval(sanitize_text_field($item->ID)) . ' with B1.lt');
                    } catch (B1Exception $e) {
                        ModelB1Orders::setB1FailedSync($item->ID);
                        ModelB1Helper::printPreB1($e->getMessage());
                        ModelB1Helper::printPreB1($e->getExtraData());
                        throw new B1CronException('Error syncing order #' . intval(sanitize_text_field($item->ID)) . ' with B1.lt');
                    }
                    $processed++;
                    echo "$i-$processed;";
                }
                ob_end_flush();
            } while ($processed == self::ORDERS_PER_ITERATION && $i < self::MAX_ITERATIONS);

            if ($initial_sync == 0) {
                ModelB1Settings::setB1Setting('initial_sync', '1');
            }

            echo 'OK';
        } catch (B1CronException $e) {
            ModelB1Orders::unsetB1SyncOrders($id);
            ModelB1Helper::printPreB1($e->getMessage());
            ModelB1Helper::printPreB1($e->getTrace());
            ModelB1Helper::printPreB1($e->getExtraData());
        }
    }

    private function generateOrderData($item, $data_prefix, $initial_sync, $tax_rate)
    {
        $post_meta = get_post_meta($item->ID);
        $db_order_currency = $post_meta['_order_currency'][0];
        $db_order_total = $post_meta['_order_total'][0];
        $db_billing_email = $post_meta['_billing_email'][0];
        $db_billing_first_name = $post_meta['_billing_first_name'][0];
        $db_billing_last_name = $post_meta['_billing_last_name'][0];
        $db_billing_company = $post_meta['_billing_company'][0];
        $db_billing_address_1 = $post_meta['_billing_address_1'][0];
        $db_billing_city = $post_meta['_billing_city'][0];
        $db_billing_country = $post_meta['_billing_country'][0];
        $db_shipping_first_name = $post_meta['_shipping_first_name'][0];
        $db_shipping_last_name = $post_meta['_shipping_last_name'][0];
        $db_shipping_company = $post_meta['_shipping_company'][0];
        $db_shipping_address_1 = $post_meta['_shipping_address_1'][0];
        $db_shipping_city = $post_meta['_shipping_city'][0];
        $db_shipping_country = $post_meta['_shipping_country'][0];
        $db_order_shipping = $post_meta['_order_shipping'][0];
        $db_prices_include_tax = $post_meta['_prices_include_tax'][0];

        $order_data = array();
        $order_data['prefix'] = $data_prefix;
        $order_data['writeOff'] = $initial_sync;
        $order_data['orderId'] = $item->ID;
        $order_data['orderDate'] = substr($item->post_date, 0, 10);
        $order_data['orderNo'] = $item->ID;
        $order_data['currency'] = $db_order_currency;
        $order_data['discount'] = 0;
        $order_data['total'] = intval(round($db_order_total * 100));
        $order_data['orderEmail'] = $db_billing_email;

        $order_data['billing']['isCompany'] = $db_billing_company == '' ? 0 : 1;
        $order_data['billing']['name'] = $db_billing_company == '' ? trim($db_billing_first_name . ' ' . $db_billing_last_name) : $db_billing_company;
        $order_data['billing']['address'] = $db_billing_address_1;
        $order_data['billing']['city'] = $db_billing_city;
        $order_data['billing']['country'] = $db_billing_country;

        $order_data['delivery']['isCompany'] = $db_shipping_company == '' ? 0 : 1;
        $order_data['delivery']['name'] = $db_shipping_company == '' ? trim($db_shipping_first_name . ' ' . $db_shipping_last_name) : $db_shipping_company;
        $order_data['delivery']['address'] = $db_shipping_address_1;
        $order_data['delivery']['city'] = $db_shipping_city;
        $order_data['delivery']['country'] = $db_shipping_country;

        if ($order_data['billing']['name'] == '') {
            $order_data['billing'] = $order_data['delivery'];
        }

        if ($order_data['delivery']['name'] == '') {
            $order_data['delivery'] = $order_data['billing'];
        }

        if ($db_prices_include_tax == 'yes') {
            $order_data['shippingAmount'] = intval(round($db_order_shipping * 100));
        } else {
            $db_order_shipping_tax = $post_meta['_order_shipping_tax'][0];
            $order_data['shippingAmount'] = intval(round(($db_order_shipping + $db_order_shipping_tax) * 100));
        }
        $order_products = ModelB1Orders::getB1OrderProducts($item->ID);
        if (!$order_products) {
            throw new B1CronException('Not found order_products data, for #' . intval(sanitize_text_field($item->ID)) . ' order.', array('order_data' => $order_data));
        }

        foreach ($order_products as $key => $product) {
            if ($product->order_item_type == 'line_item') {
                $product_id = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, '_product_id');
                $product_qty = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, '_qty');
                if (!$product_qty) {
                    throw new B1CronException('Not set item _qty');
                }

                $product_line_subtotal = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, '_line_subtotal');
                if (!$product_line_subtotal) {
                    throw new B1CronException('Not set item _line_subtotal');
                }

                $product_product_id = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, '_product_id');
                if (!$product_product_id) {
                    throw new B1CronException('Not set item _product_id');
                }

                $linked_product = ModelB1Items::getB1LinkedItem($product_product_id);

                if ($product_line_subtotal != '') {
                    if ($linked_product && ModelB1Settings::getB1Setting('orders_sync_from') == '1_1') {
                        $order_data['items'][$key]['id'] = $linked_product;
                    }
                    $order_data['items'][$key]['name'] = $product->order_item_name;
                    $order_data['items'][$key]['quantity'] = intval(round($product_qty * 100));
                    if ($tax_rate != null) {
                        $order_data['items'][$key]['vatRate'] = intval(round($tax_rate));
                    }
                    if ($db_prices_include_tax == 'yes') {
                        $order_data['items'][$key]['price'] = intval(round(get_post_meta($product_id)['_price'][0] * 100));
                        $order_data['items'][$key]['sum'] = intval(round($order_data['items'][$key]['price'] * $product_qty));
                    } else {
                        $product_tax = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, '_line_tax');
                        $order_data['items'][$key]['price'] = intval(round((get_post_meta($product_id)['_price'][0] + $product_tax) * 100));
                        $order_data['items'][$key]['sum'] = intval(round(($order_data['items'][$key]['price']) * $product_qty));
                    }
                }
            }
            if ($product->order_item_type == 'coupon') {
                $order_data['discount'] = ModelB1Orders::getB1OrderItemMeta($product->order_item_id, 'discount_amount');
            }
        }

        return $order_data;
    }

    private function getInvoice($order, $key)
    {
        $b1 = new B1(['apiKey' => $this->settings['api_key'], 'privateKey' => $this->settings['private_key']]);
        $orders = ModelB1Orders::getB1Order($order);
        if (count($orders) > 0) {
            if ($orders[0]->post_password == $key) {
                try {
                    $file_url = $b1->generateInvoiceUrl($order, ModelB1Settings::getB1Setting('shop_id'));
                    @$content = file_get_contents($file_url);
                    if ($content != false) {
                        header('Content-type: application/pdf');
                        header('Content-Disposition: attachment; filename=' . $order . '.pdf');
                        echo $content;
                        exit;
                    } else {
                        echo 'ERROR: Tokios sąskaitos nėra arba ji dar nėra sugeneruota';
                    }
                } catch (B1Exception $e) {
                    exit;
                }
            }
        }
    }

}
