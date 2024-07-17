<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean', ROLE_ADMIN, 'wh');
require 'warehouse/WareHouse.php';
/* получение пользователя из сессии */
$user = $_SESSION['userBean'];
$page = 'in_out_item';
/*
 * создать запись в логах
 * списать нужное количество из БД
 * создать запись перемещения с указанием кто отпустил , кому и когда с количеством и прочими данными
 * если списали под проект то указать проект и/или заказ
 * если списани под заказ то в заказе если был резерв то удалить списанное из резерва
 * если был резерв для другого заказа то обновить данные по резерву исключив кол-во списаное под
 * сторонние нужды. сделать документы по перемещению или то то подобное
 * запси о этом событии будут выведены в полях детали при входе в детали ЗЧ
 * Item Movements information вкладка для отображения всех перемещений деталей
 * */

/*
 * сделать списание запчасти вэтом файле и приход запчасти которая уже внесена в БД
 * при списании дать возможность выбрать из какого прихода нужно списать
 *
 */