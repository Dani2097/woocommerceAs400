<?php
const HEAD_CUSTOMER_exp = ['CODICE', 'RAGIONE SOCIALE', 'INDIRIZZO', 'COMUNE', 'CAP', 'PROVINCE', 'NAZIONE', 'LISTINO', 'AGENTE', 'SUB', 'CATEGORIA', 'MAIL', 'CV', 'IVA', 'WEBCODE', 'BLOCCATO', 'ISOIVA'];

//bisogna esportare se as400id o orderid;
function customer_export_controller()
{
    $allCusAs400 = getAllCustomerWithAs400();
    $toSearch    = [];
    $map         = [];
    foreach ($allCusAs400 as $customer) {
        $toSearch[]              = ($customer->user_id);
        $map[$customer->user_id] = $customer->meta_value;
    }
    $orders = wc_get_orders(array('date_created' => '>' . (0)));
    foreach ($orders as $order) {
        $toSearch[] = $order->get_user_id();
    }
    $toSearch        = array_unique($toSearch);
    $allCusToExports = [];
    foreach ($toSearch as $id) {
        $as400Id = '';
        if (isset($map[$id]))
            $as400Id = $map[$id];
        $wc_customer    = new WC_Customer($id);
        $email          = $wc_customer->get_email();
        $is_valid_email = str_contains($email, 'mail@mail') === false;
        if (!$is_valid_email) $email = '';
        $cusToExport       =
            [
                $as400Id,
                $wc_customer->get_first_name(),
                $wc_customer->get_billing_address(),
                $wc_customer->get_billing_city(),
                $wc_customer->get_billing_postcode(),
                $wc_customer->get_billing_state(),
                $wc_customer->get_billing_country(),
                'listino',
                'agente',
                'subcat',
                'catsconto',
                $email,
                'cv',
                'iva',
                $id,
                'bloccato',
                'isoIVA'
            ];
        $allCusToExports[] = $cusToExport;
    }
    echo '<button class="main-button-csv-importer" onclick="' . "consumateAjax(`" . str_replace('"', '\'', json_encode($allCusToExports),) . "`,'exportCustomer')" . '">
                Esporta tutti i clienti
          </button>';
}

function export_customers($_data)
{
    $jsonData  = stripslashes(html_entity_decode($_data));
    $orderData = json_decode(str_replace("'", '"', $jsonData));
    $data      = str_replace("<br/>", '', $orderData);
    $data      = str_replace("<br />", '', $data);
    $data      = str_replace("<br>", '', $data);
    $allFile   = '';
    foreach ($data as $row) {
        $str_row = '';
        foreach ($row as $col)
            $str_row .= $col . '|';
        $str_row = substr($str_row, 0, strlen($str_row) - 1) . "\n";
        $allFile .= $str_row;

    }
    $file1 = plugin_dir_path(__FILE__) . "output/CLIENTI.txt";
    $open1 = fopen($file1, "w+");
    fwrite($open1, $allFile);
    return 'done';
}

function getAllCustomerWithAs400()
{
    global $wpdb;
    $query = "SELECT * FROM wp_usermeta WHERE meta_key = 'asCusID'";
    $test  = $wpdb->get_results($query);
    if (count($test) > 0)
        return $test;
    return -1;
}