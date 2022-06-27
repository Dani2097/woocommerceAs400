<?php
const HEAD_DATA = ['Codice Articolo',
                   'Descrizione',
                   'Unita',
                   'Marchio',
                   'Vendibile',
                   'Visibile',
                   'Novità',
                   'Macro categoria',
                   'Sotto categoria',
                   'Terzo livello',
                   'Inizio vendite',
                   'Pezzi per confezione',
                   'Lotto minimo',
                   'Codice Catalogo',
                   'Codice listino base',
                   'Codice Fascia sconto',
                   'Immagine',
                   'Catalogo',
                   'Prezzo'];
function insert_product($data, $price)
{
    $jsonData       = stripslashes(html_entity_decode($data));
    $prod           = json_decode(str_replace("'", '"', $jsonData), null, 512, JSON_BIGINT_AS_STRING);
    $number_of_prod = count($prod);
    $to_insert      = array_slice($prod, 0, MAX_ITEMS_PER_API);
    $insert_later   = array_slice($prod, MAX_ITEMS_PER_API, $number_of_prod - MAX_ITEMS_PER_API);
    $to_import      = WP_PLUGIN_DIR . '/csvImporter/log/product/';
    $myfile         = fopen($to_import . date('d_m_y__H') . ".txt", "a");
    foreach ($to_insert as $product) {
        $wcProdId   = get_product_by_sku($product[0]);
        $newProduct = null;
        if ($wcProdId != 0) {
            $newProduct = new WC_Product_Simple($wcProdId);
        } else {
            $newProduct = new WC_Product_Simple();
        }
        $newProduct->set_name($product[1]);
        $newProduct->set_status('publish');
        $newProduct->set_catalog_visibility('visible');
        if (isset($product[18])) {
            $newProduct->set_price($product[18]);
            $newProduct->set_regular_price($product[18]);
        }
        $newProduct->set_sku($product[0]);
        if (isset($product[19]))
            $newProduct->set_category_ids([$product[19]]);
        $newProduct->set_sold_individually(true);
        $newID = $newProduct->save();
        createLog($myfile, $wcProdId, $product, HEAD_DATA);
    }
    if (count($insert_later) > 0) {
        echo json_encode($insert_later);
    } else echo 'done';

}



function productController($to_import)
{
    $priceData    = parseCSV($to_import . "/PREZZI.CSV", true);
    $articlesData = [];
    $row          = 0;
    $all          = [];
    if (($handle = fopen($to_import . "/ARTICOLI.CSV", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, null, "|")) !== FALSE) {
            $num     = count($data);
            $rowData = [];
            $row++;
            for ($c = 0; $c < $num; $c++) {
                array_push($rowData, str_replace("'", ' ', clean($data[$c])));
            }
            $found = false;
            foreach ($priceData as $price) {
                if (isset($price[2])) {
                    if ($data[0] == $price[2]) {
                        $catID = $rowData[7];
                        if ((int)$rowData[9] != 0) $catID = $rowData[9];
                        if ((int)$rowData[8] != 0) $catID = $rowData[8];
                        $found = true;
                        array_push($rowData, str_replace(",", ".", $price[4]));
                        $rowData[] = checkIfCatExistByAs400($catID);
                        array_push($articlesData, $rowData);
                    }
                }
            }
            $all[] = $rowData;
            if ($row < 50 && !$found)
                $articlesData[] = $rowData;
        }
        $all = array_slice($all, 0, 500);
        echo '
                <h1>Prodotti</h1>
                <h2>Sono stati trovati ' . count($all) . ' prodotti in totale e ' . count($articlesData) . ' prodotti con un prezzo cosa desidera fare?</h2>
                <button class="main-button-csv-importer" onclick="' . "consumateAjax(`" . str_replace('"', '\'', json_encode($articlesData)) . "`,'insertProduct',0," . count($articlesData) . ")" . '">
                    Inserisci tutti i prodotti con un prezzo
                 </button>
                <button class="main-button-csv-importer" onclick="' . "consumateAjax(document.getElementById('import-product-data').innerHTML,'insertProduct',0," . count($all) . ")" . '">
                    Inserisci tutti i prodotti
                </button>
                <br>
                <br>
                <label style="margin-top: 12px" for="file">Importing progress:</label>
                <br>
                <progress id="#product-progress-csv-importer" value="' . "0" . '" max="' . count($articlesData) . '"> </progress> 
                <h3 id="csv-import-product-remaining">0 rimanenti</h3>
<p id="import-product-data" style="display:none;">' . str_replace('"', '\'', json_encode($all)) . '</p>';
        fclose($handle);
    }
}

function get_product_by_sku($sku)
{

    global $wpdb;

    $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

    if ($product_id) return $product_id;

    return 0;
}