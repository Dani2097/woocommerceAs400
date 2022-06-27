<?php
const LOGPATH   = '';
const to_import = WP_PLUGIN_DIR . '/csvImporter/log/order/';

function orderController($to_import)
{
    $testa  = parseCSV($to_import . "/TESTAOC.CSV", true);
    $righe  = parseCSV($to_import . "/RIGHEOC.CSV", true);
    $temp   = ['id' => 0, 'data' => [], 'customer' => 0];
    $parsed = [];
    foreach ($testa as $head_row) {
        $temp               = ['id' => 0, 'data' => [], 'customer' => 0];
        $temp['id']         = $head_row[3];
        $temp['customerAs'] = $head_row[0];
        $temp['status']     = $head_row[6];
        $temp['customerWc'] = getUserIDFromAs400($head_row[0]);
        foreach ($righe as $row) {
            if ($temp['id'] == $row[0]) {
                $temp['data'][] = $row;
                $found          = true;
            }
        }
        $parsed[] = $temp;
    }
    echo count($parsed) . ' ordini.<br/>';

//    showDataAsTable([], $parsed);
    echo '<h1>Ordini</h1>';
    echo '<button class="main-button-csv-importer" onclick="' . "consumateAjax(`" . str_replace('"', '\'', json_encode($parsed),) . "`,'insertOrder')" . '">
                    Inserisci ordini
                  </button>';
    echo '<br>
          <br>
          <label style="margin-top: 12px" for="file">Importing progress:</label>
          <br>
          <progress id="order-progress-csv-importer" value="' . "0" . '" max="' . count($parsed) . '"></progress>
          <h3 id="csv-import-order-remaining">0 rimanenti</h3>';
}

function insert_order($data)
{
    $myfile   = fopen(to_import . date('d_m_y__H') . ".txt", "a");
    $jsonData = stripslashes(html_entity_decode($data));

    $prod               = json_decode(str_replace("'", '"', $jsonData));
    $data               = str_replace("<br/>", '', $prod);
    $data               = str_replace("<br />", '', $data);
    $data               = str_replace("<br>", '', $data);
    $number_of_customer = count($data);
    if (!is_array($data)) echo $data . 'data';
    $to_insert    = array_slice($data, 0, MAX_ITEMS_PER_API);
    $insert_later = array_slice($data, MAX_ITEMS_PER_API, $number_of_customer - MAX_ITEMS_PER_API);

    /*
     * [["2021-OC-OC-000001","M.ME.021","5","9,000","9,000","20210107","85,000000",",00",",00",",00","765,00","CONSEGNATO A SALDO"],
     * ["2021-OC-OC-000001","***","10","1,000","1,000","20210107",",000000",",00",",00",",00",",00","CONSEGNATO A SALDO"],
     * ["2021-OC-OC-000001","***","15","1,000","1,000","20210107",",000000",",00",",00",",00",",00","CONSEGNATO A SALDO"]]
     * */
    $oldProd = null;
    $all     = [];
    //345611
    foreach ($to_insert as $content) {
        $lineItems = null;
        $lineItems = [];
        foreach ($content->data as $product) {
            $ToSearch = $product[1];
            if ($ToSearch == '***') $ToSearch = $oldProd;
            else $oldProd = $ToSearch;
            $wcID        = get_product_by_sku($ToSearch);
            $lineItem    = array('quantity' => $product[3],
                                 'args'     => array(
                                     'product_id' => $wcID,
                                 ));
            $lineItems[] = $lineItem;
        };
        $customer = null;
        //todo levare e segnare come errore
        if ($content->customerWc == -1)
            $customer = new WC_Customer(4240);
        else
            $customer = new WC_Customer($content->customerWc);
        //todo:address da prendere dal indirizzi.csv
        $create_wc_order_data = (array(
            'address'        => array(
                'shipping_first_name' => $customer->get_shipping_last_name(),
                'shipping_last_name'  => $customer->get_shipping_last_name(),
                'shipping_company'    => $customer->get_shipping_company(),
                'shipping_phone'      => $customer->get_shipping_phone(),
                'shipping_address_1'  => $customer->get_shipping_address_1(),
                'shipping_address_2'  => $customer->get_shipping_address_1(),
                'shipping_city'       => $customer->get_shipping_city(),
                'shipping_state'      => $customer->get_shipping_state(),
                'shipping_postcode'   => $customer->get_shipping_postcode(),
                'shipping_country'    => $customer->get_shipping_country(),

                'billing_first_name' => $customer->get_billing_last_name(),
                'billing_last_name'  => $customer->get_billing_last_name(),
                'billing_company'    => $customer->get_shipping_company(),
                'billing_phone'      => $customer->get_billing_phone(),
                'billing_address_1'  => $customer->get_billing_address_1(),
                'billing_address_2'  => $customer->get_billing_address_2(),
                'billing_city'       => $customer->get_billing_city(),
                'billing_state'      => $customer->get_billing_state(),
                'billing_postcode'   => $customer->get_billing_postcode(),
                'billing_country'    => $customer->get_billing_country(),
                'billing_email'      => $customer->get_billing_email(),
            ),
            'user_id'        => $customer->get_id(),
            'order_comments' => '',
            'order_status'   => array(
                'status' => '',
                'note'   => '',
            ),
            'line_items'     => $lineItems,
            'fee_items'      => array(
                array(
                    'name'      => 'Delivery',
                    'total'     => 5,
                    'tax_class' => 0, // Not taxable
                ),
            ),
        ));
        $all[]                = $create_wc_order_data;

        create_wc_order($create_wc_order_data, $content);
        $myFile = '';
    }
    if (count($insert_later) > 0)
        echo json_encode($insert_later);
    else echo 'done';
}

function isEmpty($string)
{
    return (!$string || $string == '' || $string == ' ' || $string == '-');

}

function create_wc_order($data, $content)
{
    $gateways = WC()->payment_gateways->get_available_payment_gateways();
    $order    = new WC_Order();
    $myfile   = fopen(to_import . date('d_m_y__H') . ".txt", "a");
    $error    = array();
    if (!$content->customerWc || $content->customerWc == '' || $content->customerWc == ' ' || $content->customerWc == '-')
        $error['IDutente'] = true;
    if (isEmpty($data['order_status']))
        $error['StatoOrdine'] = true;
    if (count($data['line_items']) <= 0)
        $error['Prodotti'] = true;
    // Set Billing and Shipping adresses
    foreach ($data['address'] as $key => $value) {
        $type_key = $key;

        if (is_callable(array($order, "set_{$type_key}"))) {
            $order->{"set_{$type_key}"}($value);
        }
    }

    // Set other details
    $order->set_created_via('programatically');
    $order->set_customer_id($data['user_id']);
    $order->set_currency(get_woocommerce_currency());
    $order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
    $order->set_customer_note(isset($data['order_comments']) ? $data['order_comments'] : '');
    if (isset($data['payment_method']))
        $order->set_payment_method((isset($gateways[$data['payment_method']]) ? $gateways[$data['payment_method']] : isset($data['payment_method'])) ? $data['payment_method'] : 'cod');

    $calculate_taxes_for = array(
        'country'  => $data['address']['shipping_country'],
        'state'    => $data['address']['shipping_state'],
        'postcode' => $data['address']['shipping_postcode'],
        'city'     => $data['address']['shipping_city']
    );

    // Line items
    foreach ($data['line_items'] as $line_item) {
        $args    = $line_item['args'];
        $product = wc_get_product(isset($args['variation_id']) && $args['variation_id'] > 0 ? $$args['variation_id'] : $args['product_id']);
        $item_id = $order->add_product($product, $line_item['quantity'], $line_item['args']);

        $item = $order->get_item($item_id, false);

        $item->calculate_taxes($calculate_taxes_for);
        $item->save();
    }

    // Coupon items
    if (isset($data['coupon_items'])) {
        foreach ($data['coupon_items'] as $coupon_item) {
            $order->apply_coupon(sanitize_title($coupon_item['code']));
        }
    }

    // Fee items
    if (isset($data['fee_items'])) {
        foreach ($data['fee_items'] as $fee_item) {
            $item = new WC_Order_Item_Fee();

            $item->set_name($fee_item['name']);
            $item->set_total($fee_item['total']);
            $tax_class = isset($fee_item['tax_class']) && $fee_item['tax_class'] != 0 ? $fee_item['tax_class'] : 0;
            $item->set_tax_class($tax_class); // O if not taxable

            $item->calculate_taxes($calculate_taxes_for);

            $item->save();
            $order->add_item($item);
        }
    }

    // Set calculated totals
    $order->calculate_totals();

    if (isset($data['order_status'])) {
        // Update order status from pending to your defined status and save data
        $order->update_status($data['order_status']['status'], $data['order_status']['note']);
        $order_id = $order->get_id();
    } else {
        // Save order to database (returns the order ID)
        $order_id = $order->save();
    }
    fwrite($myfile, $order_id . ' AGGIUNTO   ');
    if (count($error) > 0)
        fwrite($myfile, '   |   DATI MANCANTI: ');
    foreach ($error as $key => $err) {
        if ($err)
            fwrite($myfile, $key . '  -  ');
    }
    fwrite($myfile, "\n");

    // Returns the order ID
    return $order_id;
}