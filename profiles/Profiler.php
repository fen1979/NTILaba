<?php

class Profiler
{
    /**
     * CREATION NEW SUPPLIER/MANUFACTURER FROM MODAL WINDOW ON PAGES arrival, in-out-item
     * @param $post
     * @return false|string|void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function createSupplierOnFly($post)
    {
        require_once "../core/rb-mysql.php";
        R::setup('mysql:host=localhost;dbname=nti_production', 'root', '8CwG24YwZG');
        if (!R::testConnection()) {
            exit ('No database connection');
        }
//        session_start();

        require_once '../core/Resources.php';
        require_once '../core/Utility.php';

        $sup = R::dispense(SUPPLIERS);

        $sup_name = $sup->name = $post['supplier-name'];
        $sup->description = $post['description'];
        $sup->sup_type = $post['sup_type'];
        $sup->rating = $post['rating'];
        $sup_id = R::store($sup);
        list($name, $id) = implode(',', $_POST['user-data']);
        $log_data = 'On Fly Creation used, ' . $post['request'] . ' : ' . $sup_name . ', was created by user : ' . $name;
        if (logAction($name, 'CREATION', OBJECT_TYPE[12], $log_data))
            return json_encode(['supplier_id' => $sup_id, 'supplier_name' => $sup_name, 'request' => $post['request'], 'log' => $log_data]);
        else
            return json_encode(['supplier_id' => null, 'supplier_name' => null, 'request' => $post['request'], 'log' => $log_data]);
    }
}