<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
require 'Project.php';
$page = 'history_steps';

?>

<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent([
    'title' => $pageMode,
    'active_btn' => Y['PROJECT'],
    'page_tab' => $_GET['page'] ?? null,
    'record_id' => $item->id ?? null,
    'user' => $user,
    'page_name' => $page]);


echo 'view history log steps changes';
// сделать вывод карточек в которых будут представлены данные шага и сбоку представлена информация об изменениях в данном шаге
// фото сделать маленьким и при клике пусть уеличивается
// сделать удаление шага истории или архивацию где данные шаги не будут выводится пользователю
// для админа эти шаги будут покрашены в серый и отмечены как архивированные
// сделать кнопку возврата к шагу на котором были ранее
?>

<?php
// footer and scripts
PAGE_FOOTER($page); ?>
</body>
</html>
