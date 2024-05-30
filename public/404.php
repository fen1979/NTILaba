<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="/public/images/nti_logo.png">

    <!-- Подключаем Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Подключаем Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- подключаем стили страницы -->
    <link rel="stylesheet" href="/public/css/page404.css">
    <title>404</title>
</head>
<body>
<!--  back to home page  -->
<a href="/order" class="floating-btn none" title="Back to Home Page">
    <i class="bi bi-house"></i>
</a>
<div class="container">
    <h1 class="text-404">404</h1>
    <h3 class="text-message">Oops. This page you requested not found!</h3>

    <footer class="d-none d-md-block d-flex flex-wrap justify-content-between align-items-center border-top mt-auto">
        <div class="row py-3">
            <!-- Копирайт -->
            <div class="col-md-8 text-left ms-3">
                <?= '2016 - ' . date('Y') . '&nbsp; Created by &copy; Ajeco.ltd'; ?>
            </div>

            <!-- Счетчик проектов -->
            <div class="col-md-3 text-right">
                <?= 'NTI Group - ' . R::count('projects');
                ?>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
