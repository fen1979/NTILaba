<?php
class_alias('CPController', 'CPC');

class CPController
{
    private static function backDataToRoutedPage(array $get, array $urlData): string
    {
        // routed from create project page
        $newUrl = '';
        if (isset($get['routed-from'])) {
            $url = 'customer_name=' . urlencode($urlData->name) . '&priority=' . urlencode($urlData->priority) .
                '&customer_id=' . urlencode($urlData->id);

            switch ($get['routed-from']) {
                // back to project creation page with parameters
                case 'create-project':
                    $newUrl = "Location: new_project?$url";
                    break;
                // back to order creation page with parameters
                case 'create-order':
                    $newUrl = "Location: new_order?$url";
                    break;
            }
        }
        return $newUrl;
    }

    /*========================================================== PRIVATE =====================================================================*/
    /*========================================================================================================================================*/
    /*========================================================== PUBLIC ======================================================================*/

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

    /**
     * - Добавление нового клиента
     * @param $get
     * @param $post
     * @param $user
     * @return array
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function createCustomer($get, $post, $user): array
    {
        $post = checkPostDataAndConvertToArray($post);

        $extraPhones = ['phone_1' => $post['extraPhone_1'] ?? '', 'phone_2' => $post['extraPhone_2'] ?? ''];
        $extraContact = ['contact_1' => $post['extraContact_1'] ?? '', 'contact_2' => $post['extraContact_2'] ?? ''];
        $extraEmail = ['email_1' => $post['extraEmail_1'] ?? '', 'email_2' => $post['extraEmail_2'] ?? ''];

        $name = $post['customerName'];
        $priority = $post['priorityMakat'];

        $c = R::dispense(CLIENTS);
        $c->name = $name;
        $c->head_pay = $post['headPay'];
        $c->priority = $priority;
        $c->address = $post['address'] ?? '';
        $c->phone = $post['phone'] ?? '';
        $c->contact = $post['contact'] ?? '';
        $c->email = $post['email'] ?? '';
        $c->extra_phone = json_encode($extraPhones);
        $c->extra_contact = json_encode($extraContact);
        $c->extra_email = json_encode($extraEmail);
        $c->information = $post['information'] ?? '';
        $c->date_in = str_replace('T', ' ', $post['date_in']); // дата создания clienta
        $id = R::store($c);

        _flashMessage('Customer Saved successfully!');

        if ($get != null) {
            // if routed from order or project pages back data to page
            $urlData['id'] = $id;
            $urlData['name'] = $name;
            $urlData['priority'] = $priority;
            // если клиент был создан при создании заказа/проекта
            // здесь создается обратный путь с новыми данными для заполнения полей
            $args['location'] = self::backDataToRoutedPage($get, $urlData);//
        } else {
            $args['customer_id'] = $id;
        }
        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Customer name: $name, was created in: {$post['dateIn']} <br>";
        if (isset($get['routed-from']))
            $details .= "Creation initiated from {$get['routed-from']}";
        if (!logAction($user['user_name'], 'CREATION', OBJECT_TYPE[13], $details)) {
            _flashMessage('Error! log creation!', 'danger');
        }

        return $args;
    }

    /**
     * - Обновление данных клиента
     * @param $post
     * @param $user
     * @return void
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function updateCustomerData($post, $user)
    {
        $post = checkPostDataAndConvertToArray($post);

        $extraPhones = ['phone_1' => $post['extraPhone_1'] ?? '', 'phone_2' => $post['extraPhone_2'] ?? ''];
        $extraContact = ['contact_1' => $post['extraContact_1'] ?? '', 'contact_2' => $post['extraContact_2'] ?? ''];
        $extraEmail = ['email_1' => $post['extraEmail_1'] ?? '', 'email_2' => $post['extraEmail_2'] ?? ''];

        $name = $post['customerName'];
        $priority = $post['priorityMakat'];
        $c = null;
        // Проверяем, состоит ли строка только из чисел
        if (isset($post['cuid']) && ctype_digit($post['cuid'])) {
            $c = R::load(CLIENTS, $post['cuid']);
        } else {
            _flashMessage('Customer ID is not correct !!!', 'danger');
        }
        // data before update
        //$before = $c->export();

        $c->name = $name;
        $c->head_pay = $post['headPay'];
        $c->priority = $priority;
        $c->address = $post['address'];
        $c->phone = $post['phone'];
        $c->contact = $post['contact'];
        $c->email = $post['email'];
        $c->extra_phone = json_encode($extraPhones);
        $c->extra_contact = json_encode($extraContact);
        $c->extra_email = json_encode($extraEmail);
        $c->information = $post['information'];
        $c->date_in = $post['dateIn'];
        $id = R::store($c);

        // data after update
        //$after = $c->export();
        // json_encode($before, JSON_UNESCAPED_UNICODE);
        // json_encode($after, JSON_UNESCAPED_UNICODE);
// fixme сделать правильное логирование
        // message collector (text/ color/ auto_hide = true)
        _flashMessage('Customer Updated successfully!');

        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Customer name: $name, was updated <br>";

        if (!logAction($user['user_name'], 'CREATION', OBJECT_TYPE[13], $details)) {
            // message collector (text/ color/ auto_hide = true)
            _flashMessage('Error! log creation!', 'danger');
        }
    }
}