<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="https://nti.icu">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Layout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>

    </style>
</head>
<body>
<?php
include 'core/Routing.php';
// RT0402FRE071KL, MC0402WGF1001TCE
// CPF0402B22K6E1
//$pn = 'RT0402FRE071KL';
//$in = '08394';
//$part_value = '1K 1% 1/16W';
//$owner_pn = 'NRES1';
////$r = R::findOne(STORAGE, 'manufacture_pn LIKE ? AND invoice LIKE ?', ['%'.$pn.'%', '%'.$in.'%']);
//$r = R::findOne(STORAGE, 'part_value = ? AND owner_pn = ? AND manufacture_pn LIKE ?', [
//    $part_value, $owner_pn, '%'.$pn.'%'
//]);
//
//var_dump($r->invoice);
//
//
//if (!empty($r->invoice)) {
//    $inv = explode(',', $r->invoice);
//    if (!empty($in) && !in_array($in, $inv))
//        $inv[] = $in;
//
//    $r->invoice = implode(',', $inv);
//} else {
//    $r->invoice = $in ?? '';
//}
//
//echo '<br>';
//var_dump($r->invoice);
//echo date('y/m/d');
$st = R::findAll(WH_NOMENCLATURE);
foreach ($st as $key => $value) {
    if ($value['actual_qty'] != 0) {
        $s = R::dispense(WAREHOUSE);
        $s->items_id = $key; // Ссылка на товар.
        $s->lot = 'N:' . date('m/Y') . ':TI~' . $key;
        $s->invoice = 'base flooding';
        $s->supplier = '{"name": "Mouser", "id":"1"}';   // name/id
        $id = ($value['owner'] == 'NTI') ? 14 : 2; // 14 = nti, 2 = flying
        $s->owner = '{"name":"' . $value['owner'] . '", "id":"' . $id . '"}';   // /name
        $s->owner_pn = $value['owner_pn'];
        $s->quantity = $value['actual_qty']; //  Количество товара в поставке.

        if (strpos($value['part_name'], 'Resistor') !== false) {
            $s->storage_box = 1;
            $s->storage_shelf = 'SMT-1';
        } else
            if (strpos($value['part_name'], 'Capacitor') !== false) {
                $s->storage_box = 2;
                $s->storage_shelf = 'SMT-1';
            } else
                if (strpos($value['part_name'], 'Diod') !== false || strpos($value['part_name'], 'Micro Chip') !== false ||
                    strpos($value['part_name'], 'Oscilator') !== false) {
                    $s->storage_box = 3;
                    $s->storage_shelf = 'SMT-2';
                } else
                    if (strpos($value['part_name'], 'Connector') !== false || strpos($value['part_name'], 'Pins') !== false) {
                        $s->storage_box = 5;
                        $s->storage_shelf = 'SMT-3';
                    } else {
                        $s->storage_box = 4;
                        $s->storage_shelf = 'SMT-2';
                    }


        $s->manufacture_date = $value['manufacture_date'];
        $s->expaire_date = $value['exp_date'];
        $s->date_in = $value['date_in'];
        //R::store($s);

        $quantity = $value['actual_qty'];
        $ten_percent = $quantity * 0.10;
        $rounded_ten_percent = floor($ten_percent); // Или можно использовать intval($ten_percent)

        //R::exec("UPDATE stock SET min_qty = ? WHERE id = ?", [$rounded_ten_percent, $key]);
    }
}

?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="libs/ajeco-re-max.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

    });

</script>
</body>
</html>
