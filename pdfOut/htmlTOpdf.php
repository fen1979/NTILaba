<!DOCTYPE html>
<html lang="en">
<head>
    <base href="" id="base_url">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML to PDF Example</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            /*font-size: 10px; !* Уменьшение размера шрифта *!*/
        }

        th, td {
            text-align: left;
            padding: 4px; /* Уменьшение отступов */
            border: 1px solid #ddd;
            word-wrap: break-word; /* Перенос длинных слов */
        }

        th {
            background-color: #f2f2f2;
        }

    </style>
</head>
<body>
<div id="content">
    <h1>Sample HTML Content</h1>
    <p>This is a paragraph that will be converted into a PDF file.</p>
    <?php
    // PARSING XMLS FILES WORK CODE
    include_once '../libs/XLSXreader.php';

    if ($xlsx = XLSXreader::parse('../storage/projects/JTAG-MASTER-SWITCH--for-5V-option/docs/order_bom_for_JTAG-MASTER-SWITCH--for-5V-option_.xlsx')) {
        echo '<table id="table">';
        foreach ($xlsx->rows() as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo XLSXreader::parseError();
    }
    ?>
</div>
<button id="download">Download PDF</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<script src="../libs/ajeco-re-max.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('download').addEventListener('click', () => {
            const element = document.getElementById('content');
            html2pdf().from(element).set({
                margin: 1,
                filename: 'document.pdf',
                image: {type: 'jpeg', quality: 0.98},
                html2canvas: {scale: 2},
                jsPDF: {unit: 'in', format: 'letter', orientation: 'portrait'}
            }).save();
        });

        dom.hideEmptyColumnsInTable("#table");
    });
</script>
</body>
</html>

