<?php
/*
Plugin Name: Test plugin
Description: A test plugin to demonstrate wordpress functionality with BuildVu
Author: Amy Pearson
Version: 0.1
*/
const MAX_ITEMS_PER_API        = 1;
const MAX_ITEMS_PER_API_EXPORT = 3;
if (!function_exists('wc_create_new_customer')) {
//    require_once './../woocommerce/includes/wc-user-functions.php';
}
require_once('product_model.php');
require_once('category_model.php');
require_once('general_services.php');
require_once('order_exporter_model.php');
require_once('order_controller.php');
require_once('customer_controller.php');
require_once('customer_exporter_model.php');

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


add_action('wc_ajax_myaction', 'myaction');
function myaction()
{
    if ($_POST['newAction'] == 'insertCustomer')
        insert_costumer($_POST['data']);
    else if ($_POST['newAction'] == 'insertProduct') {
        echo insert_product($_POST['data'], null);
    } else if ($_POST['newAction'] == 'insertCat') {
        insertCategory($_POST['data']);
    }
    if ($_POST['newAction'] == 'insertOrder') {
        insert_order($_POST['data']);
    }
    if ($_POST['newAction'] == 'exportCustomer') {
        echo export_customers($_POST['data']);
    }
}

/*
 * TESTINGG
 *
 * */

if (isset($_POST['action']) && !isset($_POST['newAction'])) {
    if ($_POST['action'] == 'exportOrder')
        echo exportOrders($_POST['data']);
}

/*
 * ENDTESTING
// * */
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

        function productInsertCheck(later, counter) {
            if (!later) {
                if (document.getElementById('csv-import-product-remaining'))
                    document.getElementById('csv-import-product-remaining').innerText = 0 + ' rimanenti';
                if (document.getElementById('#product-progress-csv-importer')) {
                    document.getElementById('#product-progress-csv-importer').value = document.getElementById('#product-progress-csv-importer').max;
                    setTimeout(() => {
                        alert('tutti gli elementi importati con successo')
                        document.getElementById('#product-progress-csv-importer').value = 0
                    }, 1000)
                }
            } else {
                if (document.getElementById('csv-import-product-remaining')) {
                    document.getElementById('csv-import-product-remaining').innerText = later.length + ' rimanenti';
                }
                if (document.getElementById('#product-progress-csv-importer'))
                    document.getElementById('#product-progress-csv-importer').value = document.getElementById('#product-progress-csv-importer').value + MAX_ITEM;
                consumateAjax(JSON.stringify(later), 'insertProduct', counter);
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

        function customerInsertCheck(later, counter) {
            if (!later) {
                if (document.getElementById('csv-import-customer-remaining'))
                    document.getElementById('csv-import-customer-remaining').innerText = 0 + ' rimanenti';
                if (document.getElementById('customer-progress-csv-importer')) {
                    document.getElementById('customer-progress-csv-importer').value = document.getElementById('customer-progress-csv-importer').max;
                    setTimeout(() => {
                        alert('tutti gli elementi importati con successo')
                        document.getElementById('customer-progress-csv-importer').value = 0
                    }, 1000)
                }
            } else {
                if (document.getElementById('csv-import-customer-remaining'))
                    document.getElementById('csv-import-customer-remaining').innerText = later.length + ' rimanenti';
                if (document.getElementById('customer-progress-csv-importer'))
                    document.getElementById('customer-progress-csv-importer').value = document.getElementById('customer-progress-csv-importer').value + MAX_ITEM;
                consumateAjax(JSON.stringify(later), 'insertCustomer', counter);
            }
        }

        function orderInsertCheck(later, counter) {
            if (!later) {
                if (document.getElementById('csv-import-order-remaining'))
                    document.getElementById('csv-import-order-remaining').innerText = 0 + ' rimanenti';
                if (document.getElementById('order-progress-csv-importer')) {
                    document.getElementById('order-progress-csv-importer').value = document.getElementById('order-progress-csv-importer').max;
                    setTimeout(() => {
                        alert('tutti gli elementi importati con successo')
                        document.getElementById('order-progress-csv-importer').value = 0
                    }, 1000)
                }
            } else {
                if (document.getElementById('csv-import-order-remaining'))
                    document.getElementById('csv-import-order-remaining').innerText = later.length + ' rimanenti';
                if (document.getElementById('order-progress-csv-importer'))
                    document.getElementById('order-progress-csv-importer').value = document.getElementById('order-progress-csv-importer').value + MAX_ITEM;
                consumateAjax(JSON.stringify(later), 'insertOrder', counter);
            }
        }

        function consumateAjax(req, newAction, _counter, allData) {
            if (allData && newAction == 'insertProduct') document.getElementById('#product-progress-csv-importer').max = allData;
            let counter = _counter;
            if (!counter) counter = 0;
            var j = jQuery.noConflict();
            var start_time = new Date().getTime();
            var data = {
                data: req,
                newAction
            }
            j.post('/?wc-ajax=myaction', data)
                .done(function (result) {
                    let later = null;
                    try {
                        later = JSON.parse(result.replace(/\d$/, ''));
                    } catch (e) {
                        later = null;
                    }
                    var passed = new Date().getTime() - start_time;
                    console.log('This request took ' + passed + ' ms' + ' stimati ' + (later?.length > 0 ? passed * later?.length / 1000 : 0) + "s rimasti");
                    j('#csv-importer-test').text(counter + '');
                    if (newAction === 'insertCustomer') customerInsertCheck(later, counter);
                    if (newAction === 'insertProduct') productInsertCheck(later, counter);
                    if (newAction === 'insertCat') console.log(result);
                    if (newAction === 'insertOrder') orderInsertCheck(later, counter);
                    if (newAction === 'exportCustomer') alert(result);
                    console.log(later.length)
                    if (!later)
                        console.log('ajax request completed. result=', result);

                })
                .fail(function () {
                    console.log('ajax request failed. check network log.');
                });
        }
    </script>
    <h1 style="text-align: center">Ultimate csv importer</h1>
    <h1 id="csv-importer-test" style="display: none"></h1>
    <?php
    if (is_dir($to_import)) {
        getStyle();
//        productController($to_import);
        echo '<br>';
        costumerController($to_import);
        echo '<br>';
//        orderController($to_import);
        echo '<br>';
//        categoryController($to_import);
        echo '<br>';
        echo '<h1 style="text-align: center">-------------------ESPORTAZIONE------------------------</h1><br>';
//        orderExporterController($to_import);
        customer_export_controller();
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
