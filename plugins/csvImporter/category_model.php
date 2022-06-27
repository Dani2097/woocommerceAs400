<?php

function categoryController($to_import)
{

    $category = parseCSV($to_import . '/MACRO.csv', false);
    echo '<h1>Categorie</h1>';
    echo '<button class="main-button-csv-importer" onclick="' . "consumateAjax(`" . str_replace('"', '\'', json_encode($category),) . "`,'insertCat')" . '">
                Inserisci categorie
          </button><br/><br/>';
}

//ALLORA CONTROLLO SE ESISTE LA CATEGORIA, EFFETTUO LA CREAZIONE E AGGIUNGO IL META
//in caso che esiste la aggiorno e faccio lupdate del meta
function insertCategory($data)
{
    $jsonData = stripslashes(html_entity_decode($data));
    $cat      = json_decode(str_replace("'", '"', $jsonData));
    foreach ($cat as $toParse) {
        $dbId = checkIfCatExistByAs400($toParse[0]);
        if ($dbId == -1) {
            echo $toParse[0] . " da aggiungere      ";
            $inserted = wp_insert_term(
                $toParse[1],
                'product_cat'
            );
            if (is_wp_error($inserted)) {
                echo 'Errore imprevisto';
            } else
                add_term_meta($inserted['term_id'], 'asID', $toParse[0]);
        } else {
            echo $toParse[0] . " da modificare ha id $dbId      ";
            wp_update_term(
                $dbId,
                'product_cat', // the taxonomy
                array('name' => $toParse[1])
            );
        }
    }
    echo 'done';
}

function getCategories()
{
    $api_response = wp_remote_get('https://woocommerce.dlgtek.com/wp-json/wc/v3/products/categories', array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('ck_86e7026d6df778909f932bae6109905709d90114:cs_029927925992267687de3da2eed64f9ddb7ed44b')
        ),

    ));
    return json_decode($api_response['body']);
}

function getCategoryIdBySlug($slug)
{
    $wpCat = getCategories();
    if (!isset($slug) || is_null($slug) || $slug != '') return;
    foreach ($wpCat as $category) {
        $slugID = explode('-', $category->slug)[0];
        if ((int)$slugID == (int)$slug) {
            $found = true;
            return $category->id;
        }
    }
    return -1;
}

function checkIfCatExistByAs400($asId)
{
    global $wpdb;
    if ((int)$asId == 0) return -1;
    $query = "SELECT * FROM wp_termmeta WHERE meta_key = 'asID' AND meta_value = $asId";
    $test  = $wpdb->get_results($query);
    if (count($test) > 0)
        return $test[0]->term_id;
    return -1;

}