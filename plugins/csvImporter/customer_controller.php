<?php
const HEAD_CUSTOMER = ['CODICE', 'RAGIONE SOCIALE', 'INDIRIZZO', 'COMUNE', 'CAP', 'PROVINCE', 'NAZIONE', 'LISTINO', 'AGENTE', 'SUB', 'CATEGORIA', 'MAIL', 'CV', 'IVA', 'WEBCODE', 'BLOCCATO', 'ISOIVA'];

function costumerController($to_Import)
{
    $_costumerData = parseCSV($to_Import . "/CLIENTI.CSV", false);
//    $costumerData         = $_costumerData;
    $costumerData = array_slice($_costumerData, 3630, 100);
    $addressData  = parseCSV($to_Import . '/INDIRIZZI.CSV', false);
    foreach ($addressData as $address) {
        for ($i = 0; $i < count($costumerData); $i++) {
            if ($address[0] == $costumerData[$i][0]) {
                $costumerData[$i][] = $address;
            }
        }
    }
    $head = ['CODICE', 'RAGIONE SOCIALE', 'INDIRIZZO', 'COMUNE', 'CAP', 'PROVINCE', 'NAZIONE', 'LISTINO', 'AGENTE', 'SUB', 'CATEGORIA', 'MAIL', 'CV', 'IVA', 'WEBCODE', 'BLOCCATO', 'ISOIVA'];
    echo '<h1>Clienti</h1>';
    echo '<h2>Sono stati trovati ' . count($costumerData) . ' clienti</h2>';

    echo '<button class="main-button-csv-importer" onclick="' . "consumateAjax(`" . str_replace('"', '\'', json_encode($costumerData),) . "`,'insertCustomer')" . '">
                Inserisci tutti i clienti
          </button>';
    echo '<br>
          <br>
          <label style="margin-top: 12px" for="file">Importing progress:</label>
          <br>
          <progress id="customer-progress-csv-importer" value="' . "0" . '" max="' . count($costumerData) . '"></progress>
          <h3 id="csv-import-customer-remaining">0 rimanenti</h3>
          ';
    showDataAsTable($head,$costumerData);

}

function insert_costumer($_data)
{
    $jsonData           = stripslashes(html_entity_decode($_data));
    $_prod              = json_decode(str_replace("'", '"', $jsonData));
    $data               = str_replace("<br/>", '', $_prod);
    $data               = str_replace("<br />", '', $data);
    $data               = str_replace("<br>", '', $data);
    $number_of_customer = count($data);
    if (!is_array($data)) echo $data . 'data';
    $to_insert    = array_slice($data, 0, MAX_ITEMS_PER_API);
    $insert_later = array_slice($data, MAX_ITEMS_PER_API, $number_of_customer - MAX_ITEMS_PER_API);
    $to_import    = WP_PLUGIN_DIR . '/csvImporter/log/customer/';
    $myfile       = fopen($to_import . date('d_m_y__H') . ".txt", "a");
    foreach ($to_insert as $cos) {
        $newID = createCostumer($cos, $myfile);
    }
    if (count($insert_later) > 0) {
        echo json_encode($insert_later);
    } else echo 'done';

}

function getUserAsId($id)
{
    $result = get_user_meta($id, 'asCusID');
    if (is_array($result)) {
        if (count($result) > 0)
            return $result[0];
    } else return false;
}

function getUserIDFromAs400($asID)
{
    global $wpdb;
    $query = "SELECT * FROM wp_usermeta WHERE meta_key = 'asCusID' AND meta_value = $asID";
    $test  = $wpdb->get_results($query);
    if (count($test) < 1) return '-1';
    return $test[0]->user_id;
}

function createCostumer($data, $myfile)
{

    $email    = 'mail@mail.mail';
    $_user    = getUserIDFromAs400($data[0]);
    $cus_name = substr($data[1], 0, 50);
    $cus_login = substr($data[1], 0, 50);

    if ($_user == '-1') {
        $new_customer_data =
            array(
                'user_login' => str_replace(' ', '_', $cus_login),
                'user_pass'  => 'MazzPassword',
                'user_email' => $data[0] . $email,
                'first_name' => $cus_name,
                'last_name'  => '',
                'role'       => 'customer',
                'source'     => 'store-api,',
            );
        $user_id           = wc_create_new_customer($new_customer_data['user_email'], $new_customer_data['user_login'], 'mazzuoliPass');

        if (is_wp_error($user_id)) {
            $error = 'wperr';
        } else {
            update_user_meta($user_id, "first_name", $cus_name);
            update_user_meta($user_id, "last_name", '');
            add_user_meta($user_id, 'asCusID', $data[0]);
        }
        $user = $user_id;
    } else {
        $user = $_user;
        update_user_meta($user, "first_name", $cus_name);
        update_user_meta($user, "last_name", '');
    }
    if (isset($data))
        if (isset($data[17])) {
            if (isset($data[17][3])) {
                $shipping = [$data[17][0], $data[17][1], $data[17][2], $data[17][3], $data[17][4], $data[17][5], $data[17][6], $data[17][7], $data[17][8]];
            } else {
                $shipping = [$data[0], '', $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[0] . $email];
            }
        } else {
            $shipping = [$data[0], '', $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[0] . $email];
        }
    $address = array(
        'first_name' => $cus_name,
        'last_name'  => '',
        'company'    => $cus_name,
        'email'      => $data[0] . $email,
        'phone'      => '777-777-777-777',
        'address_1'  => $data[2],
        'address_2'  => '',
        'city'       => $data[3],
        'state'      => $data[5],
        'postcode'   => $data[4],
        'country'    => $data[6]
    );
    if (!is_object($user)) {
        update_user_meta($user, "billing_first_name", $address['first_name']);
        update_user_meta($user, "billing_last_name", $address['last_name']);
        update_user_meta($user, "billing_company", $address['company']);
        update_user_meta($user, "billing_email", $address['email']);
        update_user_meta($user, "billing_address_1", $address['address_1']);
        update_user_meta($user, "billing_address_2", $address['address_2']);
        update_user_meta($user, "billing_city", $address['city']);
        update_user_meta($user, "billing_postcode", $address['postcode']);
        update_user_meta($user, "billing_country", 'US');
        update_user_meta($user, "billing_state", $address['state']);
        update_user_meta($user, "billing_phone", $address['phone']);
        update_user_meta($user, "billing_country", $address['country']);

        update_user_meta($user, "shipping_first_name", $shipping[2]);
        update_user_meta($user, "shipping_last_name", $shipping[2]);
        update_user_meta($user, "shipping_company", $shipping[2]);
        update_user_meta($user, "shipping_address_1", $shipping[3]);
        update_user_meta($user, "shipping_address_2", '');
        update_user_meta($user, "shipping_city", $shipping[4]);
        update_user_meta($user, "shipping_postcode", $shipping[5]);
        update_user_meta($user, "shipping_country", $shipping[7]);
        update_user_meta($user, "shipping_state", $shipping[6]);
        update_user_meta($user, "shipping_country", $address['country']);
        do_action("wc-admin_import_customers", $user, $data[1]);
        createLog($myfile, $user, $data, HEAD_CUSTOMER);
    } else         createLog($myfile, $user->get_error_message(), $data, HEAD_CUSTOMER);

    return $user;
}