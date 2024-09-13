<?php

class OrderManager
{
    private object $user;
    private ?object $order = null;
    private ?object $project = null;
    private ?object $client = null;
    private array $btnSubmit;
    private string $titleText;
    private string $page;
    private ?array $result = null;

    public function __construct($user)
    {
        $this->user = $user;
        $this->btnSubmit = ['text' => 'Create new order', 'name' => 'createOrder'];
        $this->titleText = 'Order Creation';
        $this->page = 'new_order';
    }

    // Метод для обработки GET-запросов и загрузки заказа на редактирование
    public function handleGetRequest($requestData): void
    {
        $requestData->executeIfAnyGetKeyExists(['edit-order', 'pid', 'orid'], function ($cleanedData) {
            $this->order = R::load(ORDERS, $cleanedData['orid']);
            $this->project = R::load(PROJECTS, $this->order->projects_id);
            $this->client = R::load(CLIENTS, $this->order->customers_id);

            $this->btnSubmit = ['text' => 'Update this order', 'name' => 'updateOrder'];
            $this->titleText = 'Editing Order';
            $this->page = 'edit_order';
        });
    }

    // Метод для поиска свободных мест на складе (вызывается через AJAX)
    public function checkForStorageBoxes($postData): void
    {
        if (isset($postData['search-for-storage-box'])) {
            echo WareHouse::getEmptyBoxForItem($postData, 'order_kit');
            exit;
        }
    }

    // Метод для создания нового заказа
    public function createOrder($postData): void
    {
        if (isset($postData['createOrder'])) {
            $this->project = R::load(PROJECTS, _E($postData['project_id']));
            $this->client = R::load(CLIENTS, _E($postData['customer_id']));
            $result = Orders::createOrder($this->user, $this->client, $this->project, $postData);
            $orderId = $result[1];

            if (!$result[0]) {
                _flashMessage('Can not write log information', 'danger');
            } else {
                redirectTo("check_bom?orid=$orderId&pid={$this->project->id}");
            }
        }
    }

    // Метод для обновления информации о заказе
    /* TODO updating order information ?????? разобратся где оно берется! да я забыл поэтому и записываю */
    public function updateOrder($postData): void
    {
        if (isset($postData['editOrder'])) {
            $this->project = R::load(PROJECTS, _E($postData['project_id']));
            $this->client = R::load(CLIENTS, _E($postData['customer_id']));
            $this->order = R::load(ORDERS, _E($postData['order_id']));

            $res = Orders::updateOrderInformation($this->user, _E($postData['order_id']), $postData, $this->project, $this->client);
            if ($res) {
                redirectTo("check_bom?orid={$postData['order_id']}&pid={$this->project->id}");
            }
        }
    }

    // Метод для создания заказа из списка проектов
    public function createOrderFromProject($getData): void
    {
        if (isset($getData['pid']) && isset($getData['nord'])) {
            $this->project = R::load(PROJECTS, _E($getData['pid']));
            $this->client = R::findOne(CLIENTS, 'name = ?', [$this->project->customername]);
        }
    }

    // Метод для обновления существующего заказа
    public function updateExistingOrder($postData, $getData): void
    {
        if (isset($postData['updateOrder']) && !empty($postData['order-id'])) {
            $result = Orders::updateOrderInformation($this->user, _E($postData['order-id']), $postData, true, true);
            $this->result = $result;
            redirectTo("edit-order?edit-order&orid={$getData['orid']}&pid={$result['pid']}");
        }
    }

    // Метод для получения данных страницы
    public function getPageData(): array
    {
        return [
            'order' => $this->order,
            'project' => $this->project,
            'client' => $this->client,
            'btnSubmit' => $this->btnSubmit,
            'titleText' => $this->titleText,
            'page' => $this->page,
            'result' => $this->result,
        ];
    }
}
