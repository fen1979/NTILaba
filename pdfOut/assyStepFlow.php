<?php
require_once 'libs/tcpdf/tcpdf.php';

if (isset($_GET['pid'])) {

    class assyStepFlow extends TCPDF
    {
        protected string $projectName;
        protected string $desc;
        protected string $video;
        protected string $revision;
        protected string $date;
        protected bool $validation = false;
        /**
         * @var float|int
         */
        private $headerHeight;

        public function setTitle($title)
        {
            $this->projectName = $title;
        }

        public function SetDecription($description)
        {
            $this->desc = $description;
        }

        public function SetVers($revision)
        {
            $this->revision = $revision;
        }

        public function SetStartDate($date_in)
        {
            $this->date = $date_in;
        }

        public function SetValidation($show)
        {
            $this->validation = $show;
        }

        public function getHeaderHeight()
        {
            return $this->headerHeight;
        }

        public function SetVideoLink($videoLink)
        {
            $this->video = $videoLink;
        }

        public function Header()
        {
            // Устанавливаем шрифт и выравнивание по центру
            $this->SetFont('dejavusans', 'B', 9);
            /* заполняем шапку документа данными */
            $this->Cell(23, 10, 'תאריך התחלה', 1, 0, 'C');
            $this->Cell(23, 10, 'מספר גירסה', 1, 0, 'C');

            $this->SetFont('dejavusans', 'B', 15);
            $this->Cell(99, 10, $this->projectName, 0, 1, 'C');

            // Добавляем логотип
            $this->Image('../public/images/nti_logo.png', 176, 5, 23);

            // Добавляем данные в ячейки во втором ряду
            $this->SetFont('dejavusans', 'B', 9);
            $this->Cell(23, 12, $this->date, 1, 0, 'C');
            $this->Cell(23, 12, $this->revision, 1, 0, 'C');
            $this->Cell(100, 12, '', 0, 1, 'C');

            // Ячейка для нумерации страниц под логотипом
            $this->Cell(0, 5, 'שלב ' . $this->getAliasNumPage() . ' מתוך ' . $this->getAliasNbPages(), 0, 1, 'R');

            $this->Ln(5);

            if ($this->validation) {
                $this->SetFont('dejavusans', 'B', 14);
                $this->SetFillColor(255, 0, 0); // Красный цвет задника
                $this->SetTextColor(255, 255, 255); // Белый цвет текста

                // Рисуем прямоугольник с закругленными углами
                $x = $this->GetX();
                $y = $this->GetY();
                $width = $this->getPageWidth() - $this->original_lMargin - $this->original_rMargin;
                $this->RoundedRect($x, $y, $width, 10, 3.50, '1111', 'F');

                // Добавляем текст
                $this->SetXY($x, $y);
                $this->Cell(0, 10, 'This step Must be approved!', 0, 1, 'C', 0);

                // Сброс цвета заливки и текста
                $this->SetFillColor(0, 0, 0);
                $this->SetTextColor(0, 0, 0);
            }

            $this->Ln(5);
            $this->SetFont('dejavusans', 'B', 12);
            $this->SetTextColor(0, 0, 0); // Черный цвет текста
            $this->MultiCell(0, 10, $this->desc, 0, 'L', 0);

            /*Рисуем линию от левого края страницы до правого*/
            $y_position = $this->GetY() + 3;
            $this->Line(15, $y_position, $this->getPageWidth() - 15, $y_position);

            // Добавляем текст с гиперссылкой
            if ($this->video != 'none') {
                $this->Ln(3);
                $this->SetTextColor(0, 0, 255);
                $label = "Click here to watch the video instructions";
                $url = BASE_URL . $this->video;
                $this->Write(10, $label, $url, 0, 'C', 1);
                /*Рисуем линию от левого края страницы до правого*/
                $y_position = $this->GetY();
                $this->Line(15, $y_position, $this->getPageWidth() - 15, $y_position);
            }

            $this->headerHeight = $this->GetY() + 5; // +5 для дополнительного отступа после заголовка
        }

        public function Footer()
        {

        }
    }

    $projectid = $_GET['pid'];
    $project = R::load(PROJECTS, $projectid);
    $data = R::findAll(PROJECT_STEPS, "projects_id = ?", [$project->id]);
    list($date, $hours) = explode(' ', $project->date_in);

    $pdf = new assyStepFlow(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('NTI Group');
    $pdf->SetTitle(str_replace('_', ' ', $project->projectname));
    $pdf->SetStartDate($date);
    $pdf->SetVers($project->revision);
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(0);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //routeaction

    if ($data) {
        $imgCount = 1;
        foreach ($data as $key => $value) {
            $pdf->SetValidation($value['validation'] ?? false);
            $pdf->SetDecription($value['description']);
            $pdf->SetVideoLink($value['video'] ?? 'none');

            $pdf->AddPage();
            $currentY = $pdf->getHeaderHeight();
            $pdf->SetY($currentY);

            // i Изображение
            if (!empty($value['image'])) {
                changeImageExt($value['image'], $imgCount);
                $imageFile = TEMP_FOLDER . 'step_' . $imgCount . '.jpeg';

                list($imgWidth, $imgHeight) = getimagesize($imageFile);
                $aspectRatio = $imgHeight / $imgWidth;

                // возвращает масив текущих MARGINS
                $margins = $pdf->getMargins();
                // Максимальная ширина и высота
                $maxWidth = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
                $maxHeight = $pdf->getPageHeight() - $currentY - $margins['bottom']; // Оставшееся пространство на странице

                // Масштабирование изображения
                $width = min($imgWidth, $maxWidth);
                $height = $width * $aspectRatio;

                // Уменьшение высоты изображения, если она больше доступного пространства
                if ($height > $maxHeight) {
                    $height = $maxHeight;
                    $width = $height / $aspectRatio;
                }

                $x = ($pdf->getPageWidth() - $width) / 2; // Центрирование изображения
                $pdf->Image($imageFile, $x, $currentY, $width, $height, 'JPEG');
                clearTmpFolder();
                /* счетчик изображений для имени файла */
                $imgCount++;
            }

            // i video
            if (strpos($value['video'], 'mp4') !== false) {
                // Получаем текущую вертикальную позицию
                $y = $pdf->GetY();
                $tempImagePath = addVideoFrameToPDF($value['video']); // $value['video'] - путь к файлу видео
                // Размещаем изображение ниже текста
                $pdf->Image($tempImagePath, 3, $y, 20, 20); // Подстройте координаты и размеры под ваш документ
                // Очистка временной папки
                clearTmpFolder();
            }
        }

        /* i вывод PDF пользователю в браузер, если мобилка то вывод на сохранение */
        $pdf->Output('export.pdf'); // ,'I');

    } else {

        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>Assembly step flow</title>
            <?php HeadContent(""); ?>
        </head>
        <body class="text-center">
        <h2 class="mt-3 p-2 ">Oooops. It seems we are not ready to provide you with a PDF of this project,
            most likely the reason is the lack of steps for this project!</h2>

        <a href="/add_step?pid=<?= $_GET['pid']; ?>" class="btn btn-outline-primary p-3">Want to start adding steps now?</a>
        </body>
        </html>
        <?php
    }

} else {
    header("Location: /order");
    exit();
}

function changeImageExt($patx, $count)
{
    if (file_exists($patx)) {
        // Load the WebP file
        $im = imagecreatefromwebp($patx);
        // Convert it to a jpeg file with 100% quality
        imagejpeg($im, TEMP_FOLDER . 'step_' . $count . '.jpeg', 100);
        imagedestroy($im);
    } else {
        echo 'file not found';
    }
}

function clearTmpFolder()
{
    $uploadDir = TEMP_FOLDER;
    array_map('unlink', glob("$uploadDir*.*"));
}

function addVideoFrameToPDF($videoPath)
{
    // Путь для сохранения изображения
    $tempImagePath = TEMP_FOLDER . 'frame.jpeg';
    // Команда ffmpeg для извлечения первого кадра
    $ffmpegCommand = "ffmpeg -i $videoPath -ss 00:00:01 -vframes 1 $tempImagePath";
    exec($ffmpegCommand);
    // Проверка существования изображения
    if (file_exists($tempImagePath)) {
        // Добавление изображения в PDF
        return $tempImagePath;
    }
    return false;
}
