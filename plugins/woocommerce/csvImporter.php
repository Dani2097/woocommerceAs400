<?php
/*
Plugin Name: Test plugin
Description: A test plugin to demonstrate wordpress functionality with BuildVu
Author: Amy Pearson
Version: 0.1
*/
const MAX_ITEMS_PER_API        = 1;
const MAX_ITEMS_PER_API_EXPORT = 3;

require_once('product_model.php');
require_once('category_model.php');
require_once('general_services.php');
require_once('order_exporter_model.php');
require_once('customer_controller.php');

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';


//ck_86e7026d6df778909f932bae6109905709d90114 //client
//cs_029927925992267687de3da2eed64f9ddb7ed44b //secret

add_action('wp_head', 'myplugin_ajaxurl');
function myplugin_ajaxurl()
{
    echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url('admin-ajax.php') . '";
         </script>';
}

add_action('admin_menu', 'test_plugin_setup_menu');
function test_plugin_setup_menu()
{
    add_menu_page('Test Plugin Page', 'Test Plugin', 'manage_options', 'test-plugin', 'test_init');
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'insertProduct') {
        $prod          = json_decode(str_replace("'", '"', $_POST['data']));
        $numberoOfProd = count($prod);
        $to_insert     = array_slice($prod, 0, MAX_ITEMS_PER_API);
        $insert_later  = array_slice($prod, MAX_ITEMS_PER_API, count($prod) - MAX_ITEMS_PER_API);
        $res           = insert_product($to_insert, $_POST['price']);
//    insert_product($prod, $_POST['price']);
        if ($numberoOfProd > MAX_ITEMS_PER_API) {
            echo json_encode($insert_later);
        } else echo 'done';
    }
    if ($_POST['action'] == 'insertCustomer') {
        var_dump(insert_costumer2($_POST['data'], $_POST['price']));
        die();

    }
//    if ($_POST['action'] == 'insertOrder') {
//        insert_order($_POST['data'], $_POST['price']);
//    }
    if ($_POST['action'] == 'insertCat') {
        insertCategory($_POST['data'], $_POST['price']);
    }
    if ($_POST['action'] == 'exportOrder') {
        echo exportOrders($_POST['data']);
    }
}

function test_init()
{
    $to_import = WP_PLUGIN_DIR . '/csvImporter/input';
    test_handle_post();
    ?>
    <script>
        const MAX_ITEM = 1;

        function callAjax(data, price, action) {
            var j = jQuery.noConflict();
            j.ajax({
                type: "POST",
                url: ajaxurl,
                data: {action: action, data, price},
                success: function (response) {
                    //todo cercare che tipologia di inserimento
                    console.log(action);

                    let later = false;
                    try {
                        later = JSON.parse(response.replace(/\d$/, ''));
                    } catch (e) {
                        later = null;
                    }
                    console.log({later})
                    if (action === 'exportOrder') {
                        orderExportCheck()
                    } else if (action === 'insertProduct') {
                        productInsertCheck(later, response)
                    } else alert(response)
                },
                error: function (err) {
                    console.log(err)
                }
            });
        }

        function productInsertCheck(later, response) {
            if (!later) {
                if (document.getElementById('#product-progress-csv-importer')) {
                    document.getElementById('#product-progress-csv-importer').value = document.getElementById('#product-progress-csv-importer').max;
                    setTimeout(() => {
                        alert('tutti gli elementi importati con successo')
                        document.getElementById('#product-progress-csv-importer').value = 0
                    }, 1000)
                }
            } else {
                console.log(response.replace(/\d$/, ''))
                console.log(JSON.parse(response.replace(/\d$/, '')), JSON.parse(response.replace(/\d$/, ''))?.length)
                if (document.getElementById('#product-progress-csv-importer'))
                    document.getElementById('#product-progress-csv-importer').value = document.getElementById('#product-progress-csv-importer').value + MAX_ITEM;
                callAjax(JSON.stringify(later), '0', 'insertProduct');
            }
        }

        function orderExportCheck() {
            if (document.getElementById('order-progress-csv-exporter')) {
                document.getElementById('order-progress-csv-exporter').value = document.getElementById('order-progress-csv-exporter').max;
                setTimeout(() => {
                    alert('tutti gli elementi importati con successo')
                    document.getElementById('order-progress-csv-exporter').value = 0
                }, 1000)
            }

        }
    </script>
    <h1>Ultimate csv importer</h1>
    <h2>Prodotti</h2>
    <?php
    if (is_dir($to_import)) {
        getStyle();
        $path = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/woocommerce/includes/wc-user-functions.php';
        echo $path;
        productController($to_import);
        costumerController($to_import);
        orderController($to_import);
        categoryController($to_import);

        if (!function_exists('wc_create_new_customer')) {
            echo 'la funzione non esiste 1';
            $path = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/woocommerce/includes/wc-user-functions.php';
            echo json_encode([file_exists($path), is_readable($path), is_writable($path), is_executable($path)]);
            require_once($path);
        } else {
            $id = wc_create_new_customer('aaa@aaa.it', 'asd22asd12s3', 'MazzuoliPass', []);
            echo 'la funzione esiste e ritorna ' . json_encode($id);
        }
        orderExporterController($to_import);
    }
}

function test_handle_post()
{
    // First check if the file appears on the _FILES array
    if (isset($_FILES['test_upload_pdf'])) {
        $pdf      = $_FILES['test_upload_pdf'];
        $uploaded = media_handle_upload('test_upload_pdf', 0);
        // Error checking using WP functions
        if (is_wp_error($uploaded)) {
            echo "Error uploading file: " . $uploaded->get_error_message();
        } else {
            echo "File upload successful!";
        }
    }
}


function insert_order($data, $price)
{
    $prod     = json_decode(str_replace("'", '"', $data));
    $bodyData = [
        'payment_method'       => 'test sku',
        'payment_method_title' => 'Direct Bank Transfer',
        'set_paid'             => true,
        'billing'              => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'address_1'  => '969 Market',
            'address_2'  => '',
            'city'       => 'San Francisco',
            'state'      => 'CA',
            'postcode'   => '94103',
            'country'    => 'US',
            'email'      => 'john.doe@example.com',
            'phone'      => '(555) 555-5555'
        ],
        'shipping'             => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'address_1'  => '969 Market',
            'address_2'  => '',
            'city'       => 'San Francisco',
            'state'      => 'CA',
            'postcode'   => '94103',
            'country'    => 'US'
        ],
        'line_items'           => [
            [
                'sku'      => $prod[0]->data[0][1],
                'quantity' => 2
            ],
            [
                'sku'      => $prod[0]->data[1][1] == '***' ? $prod[0]->data[0][1] : $prod[0]->data[1][1],
                'quantity' => 1
            ]
        ],
        'shipping_lines'       => [
            [
                'method_id'    => 'flat_rate',
                'method_title' => 'Flat Rate',
                'total'        => '10.00'
            ]
        ]
    ];
//    echo $prod[0]->data[0][1];
//    foreach ($prod as $product) {
//        $singleData = [
//            'sku' => $product[0],
//            'name' => $product[1], // product title
//            'type' => 'simple',
//            'regular_price' => $product[18] // product price
//        ];
//
//        array_push($bodyData['create'], $singleData);
//    }
    $api_response = wp_remote_post('https://woocommerce.dlgtek.com/wp-json/wc/v3/orders', array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('ck_86e7026d6df778909f932bae6109905709d90114:cs_029927925992267687de3da2eed64f9ddb7ed44b')
        ),
        'body'    => $bodyData
    ));
    echo json_encode($api_response);

}


function orderController($to_import)
{
    $testa  = parseCSV($to_import . "/TESTAOC.CSV", true);
    $righe  = parseCSV($to_import . "/RIGHEOC.CSV", true);
    $parsed = [];
    foreach ($righe as $row) {
        $temp       = ['id' => 0, 'data' => []];
        $temp['id'] = $row[0];
        $found      = false;
        $rowC       = 0;
        foreach ($parsed as $obj) {
            if ($temp['id'] == $obj['id']) {
                $parsed[$rowC]['data'][] = $row;
                $found                   = true;
            }
            $rowC++;
        }
        if (!$found) {
            $temp['data'][] = $row;
            $parsed[]       = $temp;
        };
    }
    echo '<button onclick="' . "callAjax(`" . str_replace('"', '\'', json_encode($parsed),) . "`,false,'insertOrder')" . '">
                    Inserisci ordini
                  </button>';
}

function insert_costumer2($_data)
{

    require_once('product_model.php');
    require_once('category_model.php');
    require_once('general_services.php');
    require_once('order_exporter_model.php');
    require_once('customer_controller.php');

    $path = $_SERVER['DOCUMENT_ROOT'];

    include_once $path . '/wp-content/plugins/woocommerce/includes/wc-user-functions.php';
    include_once $path . '/wp-config.php';
    include_once $path . '/wp-load.php';
    include_once $path . '/wp-includes/wp-db.php';
    include_once $path . '/wp-includes/pluggable.php';

    require $path . '/wp-content/plugins/woocommerce/src/Autoloader.php';
    require $path . '/wp-content/plugins/woocommerce/src/Packages.php';

    if (!\Automattic\WooCommerce\Autoloader::init()) {
        return;
    }
    \Automattic\WooCommerce\Packages::init();

    // Include the main WooCommerce class.
    if (!class_exists('WooCommerce', false)) {
        include_once $path . '/wp-content/plugins/woocommerce/includes/class-woocommerce.php';
    }

    // Initialize dependency injection.
    $GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();


    if (!function_exists('wc_create_new_customer')) {
        echo "non esiste create";
        echo json_encode([$path, file_exists($path), is_readable($path), is_writable($path), is_executable($path)]);
    } else {
        do_action("wc-admin_import_customers", [3431]);
        $nuovoutente = wc_create_new_customer('a22aa@aaa.it', 'nuovonuovo', 'MazzuoliPass', []);
        echo json_encode($nuovoutente);
        do_action("wc-admin_import_customers", [$nuovoutente]);
    }
}

///********************Routes
//function my_awesome_func( $data ) {
//    $obj=[];
//    $obj['title']='asd';
//    return $obj;
//}
//add_action('rest_api_init', function () {
//    register_rest_route( 'plugin', 'get', array(
//        'methods' => WP_REST_SERVER::READABLE,
//        'callback' => 'my_awesome_func',
//    ) );
//} );-->
function getStyle()
{
    echo '<style>
progress {
  border-radius: 7px; 
  width: 80%;
  max-width: 500px;
  margin-top: 12px;
  height: 16px;
  box-shadow: 1px 1px 4px rgba( 0, 0, 0, 0.2 );
}
progress::-webkit-progress-bar {
  background-color: white;
  border-radius: 7px;
}
progress::-webkit-progress-value {
  background: linear-gradient(45deg, greenyellow, darkgreen);
  border-radius: 7px;
//  box-shadow: 1px 1px 5px 3px rgba( 255, 0, 0, 0.8 );
}
progress::-moz-progress-bar {
  /* style rules */
}
.main-button-csv-importer:hover{
box-shadow: none;
}
.main-button-csv-importer{
    background-color: #04AA6D!important;
    border-radius: 5px;
    box-shadow: 1px 1px 5px #0007;
    cursor: pointer;
    color: white;
    border:none;
    font-size: 17px;
    font-family: "Source Sans Pro", sans-serif;
    padding: 6px 18px;
}
.main-button-csv-importer.exporter{
        background-color: #028793!important;
}
</style>';
}

?>
