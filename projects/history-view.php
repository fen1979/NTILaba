<?php

$user = $_SESSION['userBean'];
$page = 'history_steps';
echo 'view history log steps changes';
// сделать вывод карточек в которых будут представлены данные шага и сбоку представлена информация об изменениях в данном шаге
// фото сделать маленьким и при клике пусть уеличивается
// сделать удаление шага истории или архивацию где данные шаги не будут выводится пользователю
// для админа эти шаги будут покрашены в серый и отмечены как архивированные
// сделать кнопку возврата к шагу на котором были ранее

// NAVIGATION BAR
$navBarData['title'] = $pageMode;
$navBarData['active_btn'] = Y['PROJECT'];
$navBarData['page_tab'] = $_GET['page'] ?? null;
$navBarData['record_id'] = $item->id ?? null;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);