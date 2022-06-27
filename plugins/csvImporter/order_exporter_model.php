<?php
const TESTA_1_HEAD   = ['Codice riga', 'N ordine', 'Data e ora', 'Data di consegna', 'Codice cliente', 'Codice agente', 'Note ordine', 'Codice cliente woocommerce', 'Tipo di pagamento', 'Stato', 'Data di pagamento', 'Importo netto', 'Fattura', 'Ritiro in sede'];
const ADDRESS_2_HEAD = ['Codice riga', 'N ordine', 'Data e ora', 'Ragione sociale', 'Indirizzo', 'Cap', 'Comune', 'Provincia', 'Nazione', 'Tipo indirizzo', 'Ragione Sociale nuovo cliente', 'Indirizzo nuovo cliente', 'CAP nuovo cliente', 'Comune nuovo cliente', 'Provincia nuovo cliente', 'Nazione nuovo cliente', 'P.IVA', 'SDI', 'PEC', 'MAIL', 'TIPO DITTA', 'NOME', 'ISO P.IVA'];
const DETAIL_3_HEAD  = ['Codice riga', 'N ordine', 'Data e ora', 'Codice cliente', 'Codice agente', 'Codice prodotto', 'QNT', 'Note articolo', 'Note articolo', 'prezzo a pezzo lordo', '1 flag sconto', '2 flag sconto', '3o flag', '4o flag', '5 flag', '6 flag', '1 sconto', '2 sconto', '3o sconto', '4o sconto', '5 sconto', '6 sconto', 'importo netto totale'];
function orderExporterController()
{
    $testa1toSend   = [];
    $address2toSend = [];
    $detail3toSend  = [];
    $testaObj       = array(
        'codRiga'        => '01',
        'nOrdine'        => '',
        'orderTime'      => '',
        'shippingTime'   => '',
        'customerCodeAs' => '',
        'agentCode'      => '',
        'orderNote'      => '',
        'customerCodeWc' => '',
        'payment'        => '',
        'status'         => '',
        'paymentTime'    => '',
        'net'            => '',
        'invoice'        => '',
        'noShipping'     => ''
    );
    $orders         = wc_get_orders(array('numberposts' => -1));
    $addressObj     = array();
    $detailObj      = array();
    foreach ($orders as $order) {
        $customer = new WC_Customer($order->get_id());
        $payment  = $order->get_payment_method();
        if ($payment == 'cod') $payment = 'Pagamento alla consegna';
        $asUserID  = getUserAsId($order->get_customer_id());
        $orderDate = getOrderDate($order->get_date_created());
        //SETTIAMO LA PRIMA RIGA
        $newOrder                   = $testaObj;
        $newOrder['nOrdine']        = $order->get_id();
        $newOrder['orderTime']      = $orderDate;
        $newOrder['shippingTime']   = 'che ci metto qui?';
        $newOrder['customerCodeAs'] = $asUserID;
        $newOrder['agentCode']      = 'Ah boh';
        $newOrder['orderNote']      = $order->get_customer_note();
        $newOrder['payment']        = $payment;
        $newOrder['customerCodeWc'] = $order->get_customer_id();
        $newOrder['status']         = $order->get_status();
        $newOrder['paymentTime']    = $order->get_date_paid();
        $newOrder['net']            = $order->get_total();
        $newOrder['invoice']        = $order->get_billing_state();
        $newOrder['noShipping']     = $order->get_shipping_method();

        $testa1toSend[] = $newOrder;

        //SETTIAMO LA SECONDA RIGA
        $addressObj['codRiga']           = '02';
        $addressObj['nOrdine']           = $order->get_id();
        $addressObj['orderTime']         = $orderDate;
        $addressObj['business_name']     = $order->get_shipping_last_name() . ' ' . $order->get_shipping_first_name();
        $addressObj['address']           = $order->get_shipping_address_1();
        $addressObj['cap']               = $order->get_shipping_postcode();
        $addressObj['location']          = $order->get_shipping_city();
        $addressObj['province']          = $order->get_shipping_state();
        $addressObj['nation']            = $order->get_shipping_country();
        $addressObj['type']              = 'boh forse aziendale';
        $addressObj['business_name_new'] = $customer->get_last_name() . ' ' . $customer->get_first_name();
        $addressObj['address_new']       = $order->get_shipping_address_1();
        $addressObj['cap_new']           = $order->get_shipping_postcode();
        $addressObj['location_new']      = $order->get_shipping_city();
        $addressObj['province_new']      = $order->get_shipping_state();
        $addressObj['nation_new']        = $order->get_shipping_country();
        $addressObj['iva']               = 'iva';
        $addressObj['cf']                = 'cf';
        $addressObj['sdi']               = 'sdi';
        $addressObj['pec']               = $order->get_billing_email();
        //todo prendermi i dati di sdi, se esiste non sei una persona fisica
        $addressObj['business_type'] = $order->get_total();
        $addressObj['name']          = $customer->get_last_name() . ' ' . $customer->get_first_name();
        $addressObj['iva_iso']       = 'IT';
        $address2toSend[]            = $addressObj;
        //SETTIAMO LE ALTRE N RIGHE
        foreach ($order->get_items() as $item) {
            $quantity = $item->get_quantity();
            $subtotal = $item->get_subtotal();

            $price_incl_tax = $order->get_item_subtotal($item, true, true);

            $detailObj['codRiga']        = '03';
            $detailObj['nOrdine']        = $order->get_id();
            $detailObj['orderTime']      = $orderDate;
            $detailObj['customerCodeAs'] = $asUserID;
            $detailObj['agentCode']      = 'ah boh';
            //todo prendermi as400 prodCode
            $detailObj['prodCode']    = $item->get_id();
            $detailObj['qnt']         = $quantity;
            $detailObj['note']        = 'product_note?';
            $detailObj['note_extra']  = 'product_note2';
            $detailObj['priceSingle'] = $price_incl_tax;

            $detailObj['flag']       = 'flag sconto 1';
            $detailObj['flag_two']   = 'flag sconto 2';
            $detailObj['flag_three'] = 'flag sconto 3';
            $detailObj['flag_four']  = 'flag sconto 4';
            $detailObj['flag_five']  = 'flag sconto 5';
            $detailObj['flag_six']   = 'flag sconto 6';

            $detailObj['discount']       = 'valore sconto 1';
            $detailObj['discount_two']   = 'valore sconto 2';
            $detailObj['discount_three'] = 'valore sconto 3';
            $detailObj['discount_four']  = 'valore sconto 4';
            $detailObj['discount_five']  = 'valore sconto 5';
            $detailObj['discount_six']   = 'valore sconto 6';

            $detailObj['netTotal'] = $subtotal;
            $detail3toSend[]       = $detailObj;
        }
    }
//    showDataAsTable(DETAIL_3_HEAD, $detail3toSend);
    $parsed['first_file']  = $testa1toSend;
    $parsed['second_file'] = $address2toSend;
    $parsed['third_file']  = $detail3toSend;
    echo '<h1>ESPORTA ORDINI</h1>';
    echo '<button class="main-button-csv-importer exporter" onclick="' . "callAjax(`" . str_replace('"', '\'', json_encode($parsed),) . "`,false,'exportOrder')" . '">
        Start Exporting
          </button>';
    echo '<br>';
    echo '<br>';
    echo '<label style="margin-top: 12px" for="file">Importing progress:</label>';
    echo '<br>';
    echo '<progress id="order-progress-csv-exporter" value="' . "0" . '" max="' . count($testa1toSend) . '"> 32% </progress>';
}

function exportOrders($data)
{
    $orderData      = json_decode(str_replace("'", '"', $data));
    $testa1toSend   = $orderData->first_file;
    $address2toSend = $orderData->second_file;
    $detail3toSend  = $orderData->third_file;
    $i              = 0;
    foreach ($address2toSend as $data) {
        $id              = $testa1toSend[$i]->nOrdine;
        $filtered_prod   = array_filter($detail3toSend, fn($v) => $v->nOrdine == $id);
        $thirdFileString = '';
        foreach ($filtered_prod as $prod) {
            $thirdFileString .= generateStringTxt($prod);
        }
        $firstFileString  = generateStringTxt($testa1toSend[$i]);
        $secondFileString = generateStringTxt($data);
        $file1            = plugin_dir_path(__FILE__) . "output/ord_$id.txt";
        $open1            = fopen($file1, "w+");
        fwrite($open1, $firstFileString . $secondFileString . $thirdFileString);
        fclose($open1);
        $i++;
    }
    return json_encode($data);
}

function generateStringTxt($data)
{
    $string = '';
    foreach ($data as $row) {
        $toAdd = $row;
        if (strlen($row) <= 0) $toAdd = ' ';
        $string = $string . $toAdd . '|';

    }
    return substr($string, 0, strlen($string) - 1) . "\n";
}

function getOrderDate($time)
{
    try {
        $orderDate = new DateTime($time);
        $orderDate = $orderDate->format('YmdHis');
    } catch (Exception $e) {
        echo $e;
    }
    return $orderDate;
}