<?php
require_once 'libs/tcpdf/tcpdf.php';

if (isset($_GET['pid']) && isset($_GET['orid'])) {
    // i route_610 P&P ???
    class ORDER_DETAILS extends TCPDF
    {
        protected string $order_id;
        protected string $unit_name;
        protected string $date;
        protected string $d_date;

        public function setDocumentName($x)
        {
            $this->order_id = $x;
        }

        public function setDocumentRevision($x)
        {
            $this->unit_name = $x;
        }

        public function setStartDate($x)
        {
            $this->date = $x;
        }

        public function setEndDate($x)
        {
            $this->d_date = $x;
        }

        public function Header()
        {
            $this->SetFont('dejavusans', 'B', 20);
            $this->Cell(95, 18, 'Order ID: ' . $this->order_id, 0, 1, 'L');
            $this->Cell(95, 18, 'Unit: ' . $this->unit_name, 0, 1, 'L');
            $this->Cell(95, 18, 'Created: ' . $this->date, 0, 1, 'L');
            $this->Cell(95, 18, 'Dead Line: ' . $this->d_date, 0, 0, 'L');

            //i Добавляем логотип
            $this->Image('public/images/nti_logo.png', 176, 5, 23);
        }

        // Метод для проверки и добавления новой страницы при необходимости
        public function checkAndAddNewPage($currentHeight)
        {
            if ($this->GetY() + $currentHeight > $this->getPageHeight() - $this->getBreakMargin()) {
                $this->AddPage();
            }
        }
    }

    // Создание объекта PDF
    $pdf = new ORDER_DETAILS(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('NTI Group');
    $pdf->SetTitle('NTI Group');
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(6);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    /*i получение данных из проекта */
//    $settings = getUserSettings($_SESSION['userBean'], '');
    $projectid = _E($_GET['pid']);
    $orderid = _E($_GET['orid']);
    $project = R::load(PRODUCT_UNIT, $projectid);
    $order = R::load(ORDERS, $orderid);


    /*i добавляем нужные данные о документе в титул документа */
    $pdf->setDocumentName($order->id);
    $pdf->setDocumentRevision($project->projectname);
    $pdf->setStartDate($order->date_in); // дата создания документа
    $pdf->setEndDate($order->date_out); // дата создания документа

    /*  Добавление страницы */
    $pdf->AddPage();
    /*Рисуем линию от левого края страницы до правого*/
    $y_position = 75;
    $pdf->Line(15, $y_position, $pdf->getPageWidth() - 15, $y_position);
    /* спецификация заказа */
    $pdf->Ln(55);

    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->Cell(180, 7, 'Customer: ' . $order->customer_name, 1, 1, 'L');
    $pdf->Cell(180, 7, 'Order Amount: ' . $order->order_amount . ' pcs', 1, 1, 'L');
    $pdf->Cell(180, 7, 'FAI: ' . $order->fai_qty . ' pcs', 1, 1, 'L');
    $pdf->Cell(180, 7, 'Status: ' . SR::getResourceValue('status', $order->status), 1, 1, 'L');
    $pdf->Cell(180, 7, 'Stor BOX: ' . $order->storage_box, 1, 1, 'L');
    $pdf->Cell(180, 7, 'Stor Shelf: ' . $order->storage_shelf, 1, 1, 'L');

    /*Рисуем линию от левого края страницы до правого*/
//    $y_position = 85;
//    $pdf->Line(40, $y_position, $pdf->getPageWidth() - 40, $y_position);

    /* Спутик папке докс проекта */
    $absolutePath = $project->projectdir . 'docs/' . $project->projectname . '_routecard.pdf';
    /* и выводим в браузер для дальнейших манипуляций */
    $pdf->Output($absolutePath);
} else {
    // если данные не пришли то возвращаем пользователя на страницу заказы
    redirectTo('order');
}
// i fopenLocal() in file tcpdf_static.php was changed !!!