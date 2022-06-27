<?php
function parseCSV($to_import, $clean)
{
    $dataToSend = [];
    $row        = 0;
    if (($handle = fopen($to_import, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, null, "|")) !== FALSE) {
            $num     = count($data);
            $rowData = [];
            $row++;
            for ($c = 0; $c < $num; $c++) {
                $str = str_replace("'", ' ', $data[$c]);
                array_push($rowData, !$clean ? clean($str) : $str);
            }
            array_push($dataToSend, $rowData);
        }
        fclose($handle);
        return $dataToSend;
    }
}

function clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z\d@.,\-]/', '', $string); // Removes special chars.
    $string = str_replace('-', ' ', $string); // Replaces all spaces with hyphens.

    return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

function createLog($file, $woocommerceID, $singleData, $head)
{
    $errors = [];
    $row    = 0;
    foreach ($head as $_name) {
        $name = str_replace(' ', '_', $_name);
        if ((!isset($singleData[$row])) || (($singleData[$row]) == '0') || $singleData[$row] == '' || $singleData[$row] == ' '|| $singleData[$row] == '-') $errors[$name] = true;
        $row++;
    }

    if ($woocommerceID == 0) {
        fwrite($file, 'As400 ID: ' . $singleData[0] . '  -  WooCommerceId: ' . $woocommerceID . "  -  AGGIUNTO  -  ");
    } else {
        fwrite($file, 'As400 ID: ' . $singleData[0] . '  -  WooCommerceId: ' . $woocommerceID . "  -  MODIFICATO");
    }
    if (count($errors) > 0)
        fwrite($file, '   |   DATI MANCANTI: ');
    foreach ($errors as $key => $error) {
        if ($error)
            fwrite($file, $key . '  -  ');
    }
//    fwrite($file, "[$product[11],$head[11]}");
    fwrite($file, "\n");
}

function showDataAsTable($head, $data)
{
    echo '<div style="display: flex;gap: 16px;">';
    foreach ($head as $col) {
        echo '  <p style="flex: 1;word-break:break-all;width: max-content; max-width: 75px;min-width: 75px;text-align: center;font-weight: bold">' . $col . '-</p>';
    }
    echo '</div>';

    foreach ($data as $row) {
        echo '<div style="display: flex;gap: 16px;">';
        foreach ($row as $col) {
            if (strlen(json_encode($col)) > 0)
                echo '<p style="flex: 1;word-break:break-all;width: max-content; max-width: 75px;min-width: 75px;text-align: center">' . json_encode($col) . '</p>';
            else echo '<p style="flex: 1;word-break:break-all;width: max-content; max-width: 75px;min-width: 75px;text-align: center">-</p>';
        }
        echo '</div>';
    }
}

function parseDuplicateCustomerName($name)
{

    $one       = substr($name, 0, (int)strlen($name) / 2);
    $two       = substr($name, (int)strlen($name) / 2);
    $to_return = '';
    if (trim($one) === trim($two))
        $to_return = $one;
    return ($name);
}