<?php
require_once 'libs/tcpdf/tcpdf.php';

if (isset($_GET['pid']) && isset($_GET['orid'])) {
    // i route_610 P&P ???
    class routes_sample extends TCPDF
    {
        protected string $doc_name;
        protected string $doc_vers;
        protected string $date;

        public function setDocumentName($x)
        {
            $this->doc_name = $x;
        }

        public function setDocumentRevision($x)
        {
            $this->doc_vers = $x;
        }

        public function setStartDate($x)
        {
            $this->date = $x;
        }

        public function Header()
        {
            // Устанавливаем шрифт и выравнивание по центру
            $this->SetFont('dejavusans', 'B', 9);
            /* заполняем шапку документа данными */
            $this->Cell(25, 18, 'תאריך התחלה', 1, 0, 'C');
            $this->Cell(25, 18, 'מספר גירסה', 1, 0, 'C');

            $this->SetFont('dejavusans', 'B', 15);
            $this->Cell(95, 18, $this->doc_name, 0, 0, 'C');

            //i Добавляем логотип
            $this->Image('public/images/nti_logo.png', 176, 5, 23);
            $this->Cell(45, 18, '', 0, 1, 'C'); // Пустая ячейка под логотипом

            //i Добавляем данные в ячейки во втором ряду
            $this->SetFont('dejavusans', 'B', 9);
            $this->Cell(25, 8, $this->date, 1, 0, 'C');
            $this->Cell(25, 8, $this->doc_vers, 1, 0, 'C');
            $this->Cell(65, 8, 'QAW1-100', 1, 0, 'C');// doc type

            //i Ячейка для нумерации страниц под логотипом
            $this->Cell(35, 8, 'עמוד ' . $this->getAliasNumPage() . ' מתוך ' . $this->getAliasNbPages(), 1, 1, 'L');
        }

        public function Footer()
        {
            $this->SetFont('dejavusans', 'B', 5);
            $this->Cell(0, 6, 'עמוד ' . $this->getAliasNumPage() . ' מתוך ' . $this->getAliasNbPages(), 0, 0, 'C');
        }

        // Метод для добавления ячеек с автоматическим управлением размером шрифта
        public function addCellWithAutoFont($w, $h, $text, $border, $ln, $align, $maxChar, $fill = false)
        {
            // Устанавливаем размер шрифта в зависимости от длины текста
            if (mb_strlen($text) > $maxChar) {
                $this->SetFont('dejavusans', '', 6);
            } else {
                $this->SetFont('dejavusans', '', 7);
            }

            // Увеличиваем высоту ячейки при необходимости
            $height = (mb_strlen($text) > $maxChar * 2) ? $h * 2 : $h;
            $this->MultiCell($w, $height, $text, $border, $align, $fill, $ln);
            $this->SetFont('dejavusans', '', 7); // Восстанавливаем начальный размер шрифта
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
    //    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetFooterMargin(6);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    /*i получение данных из проекта */
    $projectid = _E($_GET['pid']);
    $orderid = _E($_GET['orid']);
    $project = R::load(PRODUCT_UNIT, $projectid);
    $order = R::load(ORDERS, $orderid);
    $assy_steps = R::findAll(PROJECT_STEPS, 'projects_id = ?', [$projectid]);
    $assy_progress = R::findAll(ASSY_PROGRESS, 'orders_id = ?', [$orderid]);


    /*i добавляем нужные данные о документе в титул документа */
    $pdf->setDocumentName($project->projectname ?? '');
    $pdf->setDocumentRevision($project->route_vers ?? 'A0');
    $pdf->setStartDate(date("Y-m-d")); // дата создания документа ???

    /*  Добавление страницы */
    $pdf->AddPage();
    /*Рисуем линию от левого края страницы до правого*/
    $y_position = 35;
    $pdf->Line(15, $y_position, $pdf->getPageWidth() - 15, $y_position);
    /* спецификация проекта */
    $pdf->Cell(0, 25, 'CLASS3', 0, 1, 'L');
    //    $pdf->Cell(0, 15, 'IPC610               CLASS3', 0, 1, 'L');
    /*Рисуем линию от левого края страницы до правого*/
    $y_position = 44;
    $pdf->Line(15, $y_position, $pdf->getPageWidth() - 15, $y_position);
    /* перенос строки */
    $pdf->Ln(5);
    /*Рисуем линию от левого края страницы до правого*/
    $y_position = 70;
    $pdf->Line(15, $y_position, $pdf->getPageWidth() - 15, $y_position);
    /* устанавливаем фонт для заголовка */
    $pdf->SetFont('dejavusans', 'B', 25);
    $pdf->Cell(0, 10, 'Route Card', 0, 1, 'C');
    /* перенос строки */
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'דב ניתוב לאיצור קרטיסים', 0, 1, 'C');
    /*Рисуем линию от левого края страницы до правого*/
    $y_position = 85;
    $pdf->Line(40, $y_position, $pdf->getPageWidth() - 40, $y_position);
    /* перенос строки */
    $pdf->Ln(5);

    /* устанавливаем фонт для пассивной таблицы */
    $pdf->SetFont('dejavusans', 'B', 12);
    /* первый ряд 6 ячеек  180 weight page */
    $pdf->Cell(30, 11, 'יש קשר', 1, 0, 'C');
    $pdf->MultiCell(20, 11, 'תאריך אספקה', 1, 'C', 0, 0);
    $pdf->MultiCell(20, 11, 'קאמוט בהזמנה', 1, 'C', 0, 0);
    $pdf->Cell(40, 11, 'מס חזמנה', 1, 0, 'C');
    $pdf->Cell(45, 11, 'שם ההלכה', 1, 0, 'C');
    $pdf->Cell(25, 11, 'מס לקוח', 1, 1, 'C');
    /* второй ряд 6 ячеек пустой для первого ряда */

    $pdf->Cell(30, 10, $project->customername, 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(40, 10, '', 1, 0, 'C');
    $pdf->Cell(45, 10, '', 1, 0, 'C');
    $pdf->Cell(25, 10, '', 1, 1, 'C');
    /* третий ряд 7 ячеек */
    $pdf->Cell(30, 10, '', 1, 0, 'C');
    $pdf->Cell(20, 10, 'QUANT', 1, 0, 'C');
    $pdf->Cell(20, 10, 'REV', 1, 0, 'C');
    $pdf->Cell(20, 10, 'ROHS', 1, 0, 'C');
    $pdf->Cell(10, 10, 'FAI', 1, 0, 'C');
    $pdf->Cell(55, 10, 'תואר מוצר', 1, 0, 'C');
    $pdf->Cell(25, 10, 'קומות ליצור', 1, 1, 'C');
    /* четвертый ряд 7 ячеек */
    $pdf->Cell(30, 8, '', 1, 0, 'C');
    $pdf->Cell(20, 8, $order->order_amount, 1, 0, 'C');
    $pdf->Cell(20, 8, $project->revision, 1, 0, 'C');
    $pdf->Cell(20, 8, '', 1, 0, 'C');
    $pdf->Cell(10, 8, $order->fai_qty, 1, 0, 'C');
    $pdf->Cell(55, 8, '', 1, 0, 'C');
    $pdf->Cell(25, 8, $order->order_amount, 1, 1, 'C');

    /* пятый ряд 2 ячеекn */
    $pdf->Cell(155, 8, $project->projectname ?? '', 1, 0, 'C');
    $pdf->Cell(25, 8, 'מקאט', 1, 1, 'C');

    /* заполняем таблицу рут карты полученную из БД */
    /* устанавливаем фонт для таблицы */
    $pdf->SetFont('dejavusans', 'B', 9);
    /* шапка таблицы */
    $pdf->Cell(20, 8, 'תאריך', 1, 0, 'C');
    $pdf->Cell(20, 8, 'ביקורט', 1, 0, 'C');
    $pdf->Cell(20, 8, 'תאריך', 1, 0, 'C');
    $pdf->Cell(20, 8, 'מבצע', 1, 0, 'C');
    $pdf->Cell(25, 8, 'לפי מפרט/סרטוט', 1, 0, 'C');
    $pdf->Cell(65, 8, 'פעולה', 1, 0, 'C');
    $pdf->Cell(10, 8, 'שלב', 1, 1, 'C');

    /* заполнение динамических данных */

    $maxChar = 70; // Максимальное количество символов без изменения высоты ячейки
    $cellHeight = 8; // Начальная высота ячейки
    $pdf->SetFont('dejavusans', '', 7); // устанавливаем фонт для таблицы
    if ($assy_progress) {
        // вывод рут карт прогресса после сборки проекта-заказа
        foreach ($assy_progress as $step) {
            // если есть серийный номер обиграть данное событие
            if ($step['serial_number'] != '0') {
                $pdf->Cell(155, 8, $step['serial_number'], 1, 0, 'C');
                $pdf->Cell(25, 8, 'Serial', 1, 1, 'C');
            }
            // assy step progress data
            $data = json_decode($step['route_card_body']);
            $route = R::load(ROUTE_ACTION, $data->route_id);
            // Проверка, достаточно ли места на странице для следующей строки
            if ($pdf->GetY() + $cellHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                // Добавляем новую страницу, если места не хватает
                $pdf->AddPage();
            }

            // Текст для вывода спецификации
            $text = $route['actions'];
            // Установка размера шрифта в зависимости от длины текста
            if (mb_strlen($text) > $maxChar) {
                // Уменьшаем размер шрифта
                $pdf->SetFont('dejavusans', '', 6);
                // Проверяем, нужно ли увеличивать высоту ячейки
                // Примерный критерий для удвоения текста
                if (mb_strlen($text) > $maxChar * 2) {
                    $cellHeight = 10; // Удваиваем высоту ячейки
                }
            }

            $pdf->Cell(20, $cellHeight, '', 1, 0, 'C'); // время проверки
            $check = ($step->approved_by != '0') ? $step->approved_by : '';  // approved by if exist
            $pdf->Cell(20, $cellHeight, $check, 1, 0, 'C'); // проверил (ФИО)
            list($date, $time) = explode(' ', $data->end_time); // Разбиение строки даты и времени на две части
            $pdf->MultiCell(20, $cellHeight, "$date\n$time", 1, 'C', false, 0); // время окончания работ
            $pdf->Cell(20, $cellHeight, $data->worker_name, 1, 0, 'C'); // имя исполнителя
            $pdf->Cell(25, $cellHeight, $route['specifications'], 1, 0, 'C'); // спецификация
            $pdf->MultiCell(65, $cellHeight, $text, 1, 'C', false, 0); // наименование действия
            // Возвращаем начальный размер шрифта для следующих ячеек
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell(10, $cellHeight, $step['current_step'], 1, 1, 'C'); // номер шага  ln=1 для переноса строки
        }

    } elseif ($assy_steps) {
        // вывод пустого рут карт для ручного заполнения
        foreach ($assy_steps as $step) {
            $route = R::load(ROUTE_ACTION, $step['routeid']);
            // Проверка, достаточно ли места на странице для следующей строки
            if ($pdf->GetY() + $cellHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                // Добавляем новую страницу, если места не хватает
                $pdf->AddPage();
            }

            // Текст для вывода спецификации
            $text = $route['actions'];
            // Установка размера шрифта в зависимости от длины текста
            if (mb_strlen($text) > $maxChar) {
                // Уменьшаем размер шрифта
                $pdf->SetFont('dejavusans', '', 6);
                // Проверяем, нужно ли увеличивать высоту ячейки
                // Примерный критерий для удвоения текста
                if (mb_strlen($text) > $maxChar * 2) {
                    $cellHeight = 10; // Удваиваем высоту ячейки
                }
            }

            $pdf->Cell(20, $cellHeight, '', 1, 0, 'C'); // время проверки
            $pdf->Cell(20, $cellHeight, '', 1, 0, 'C'); // проверил (ФИО)
            $pdf->Cell(20, $cellHeight, '', 1, 0, 'C'); // время окончания работ
            $pdf->Cell(20, $cellHeight, '', 1, 0, 'C'); // имя исполнителя
            $pdf->Cell(25, $cellHeight, $route['specifications'], 1, 0, 'C'); // спецификация
            $pdf->MultiCell(65, $cellHeight, $text, 1, 'C', false, 0); // наименование действия
            // Возвращаем начальный размер шрифта для следующих ячеек
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell(10, $cellHeight, $step['step'], 1, 1, 'C'); // номер шага  ln=1 для переноса строки
        }
    }

    /* Сохраняем документ в папке докс проекта */
    $absolutePath = $project->projectdir . 'docs/' . $project->projectname . '_routecard.pdf';
    $pdf->Output($absolutePath, 'F');

    /* и выводим в браузер для дальнейших манипуляций */
    $pdf->Output($absolutePath);
} else {
    // если данные не пришли то возвращаем пользователя на страницу заказы
    header("Location: /order");
    exit();
}
//  fopenLocal() in file tcpdf_static.php was changed