<?php

/**
 * - Client Supplier Controller
 */
class CPController
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

        require_once '../core/Constants.php';
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
     * - Добавление нового или обновление данных клиента
     * @param $get
     * @param $post
     * @param $user
     * @return array
     * @throws \RedBeanPHP\RedException\SQL
     */
    public static function CustomerInformation($get, $post, $user): array
    {
        $post = checkDataAndConvertToArray($post);
        $get = checkDataAndConvertToArray($get);
        $extraPhones = ['phone_1' => $post['extraPhone_1'] ?? '', 'phone_2' => $post['extraPhone_2'] ?? ''];
        $extraContact = ['contact_1' => $post['extraContact_1'] ?? '', 'contact_2' => $post['extraContact_2'] ?? ''];
        $extraEmail = ['email_1' => $post['extraEmail_1'] ?? '', 'email_2' => $post['extraEmail_2'] ?? ''];
        $name = $post['customerName'];
        $priority = $post['priorityMakat'] ?? '0';

        // Check if customer exists in DB by name
        $existingCustomer = R::findOne(CLIENTS, 'name = ?', [$name]);

        if ($existingCustomer) {
            $flash = 'Customer Updated successfully!';
            if ($existingCustomer->phone == $post['phone'] && $existingCustomer->email == $post['email']) {
                // Update existing customer
                $c = $existingCustomer;
            } else {
                if(!isset($post['proceedUpdating'])) {
                    _flashMessage(listeners::ActionProceed(), 'danger', false);
                    return [null];
                }else{
                    $c = $existingCustomer;
                }
            }

        } else {
            $flash = 'Customer Saved successfully!';
            // Create new customer
            $c = R::dispense(CLIENTS);
            $c->date_in = str_replace('T', ' ', $post['date_in']); // Set creation date
        }

        $c->name = $name;
        $c->head_pay = $post['headPay'] ?? '0';
        $c->priority = $priority;
        $c->address = $post['address'] ?? '';
        $c->phone = $post['phone'] ?? '';
        $c->contact = $post['contact'] ?? '';
        $c->email = $post['email'] ?? '';
        $c->extra_phone = json_encode($extraPhones);
        $c->extra_contact = json_encode($extraContact);
        $c->extra_email = json_encode($extraEmail);
        $c->information = $post['information'] ?? '';
        $id = R::store($c);

        // если клиент был создан при создании заказа/проекта
        if ($get != null) {
            // здесь создается обратный путь с новыми данными для заполнения полей
            switch ($get['routed-from']) {
                // back to project creation page with parameters
                case 'create-project':
                    $url = "new_project?back-from=cpc&clid=$id";
                    break;
                // back to order creation page with parameters
                case 'create-order':
                    $url = "new_order?back-from=cpc&clid=$id";
                    break;
            }
            // переход по созданной ссылку
            redirectTo($url);
        } else {
            $args['customer_id'] = $id;
        }
        /* [     LOGS FOR THIS ACTION     ] */
        $details = "Customer name: $name, was created in: {$post['date_in']} <br>";
        if (isset($get['routed-from']))
            $details .= "Creation initiated from {$get['routed-from']}";
        if (!logAction($user['user_name'], 'CREATION', OBJECT_TYPE[13], $details)) {
            _flashMessage('Error! log creation!', 'danger');
        }

        _flashMessage($flash . '-' . $id);
        return $args;
    }
}