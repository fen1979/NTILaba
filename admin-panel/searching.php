<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');

/* searching in to users/tools/routactions by name/actions/specifications return table */
if (isset($_POST['suggest'])) {
//    // подключение Базы Данных МаринаДБ
//    require_once "../core/rb-mysql.php";
//    require_once '../core/Resources.php';
//    require_once '../core/Utility.php';
//
//    // database name = !!!-> nti_production <-!!!
//    R::setup('mysql:host=localhost;dbname=nti_production', 'root', '8CwG24YwZG');
//    // R::freeze( true ); /* тут выключение режима заморозки */
//    if (!R::testConnection()) {
//        exit ('No database connection');
//    }
//    session_start();
    /* проверяем на скрипты и другие бяки от пользователя */
    $searchTerm = '%' . _E($_POST['suggest']) . '%';

    $users = R::getAll("SELECT * FROM users WHERE user_name LIKE ?", [$searchTerm]);
    $tools = R::getAll("SELECT * FROM tools WHERE toolname LIKE ? OR specifications LIKE ?", [$searchTerm, $searchTerm]);
    $rcards = R::getAll("SELECT * FROM routeaction WHERE specifications LIKE ? OR actions LIKE ?", [$searchTerm, $searchTerm]);

    if (!$users && !$tools && !$rcards) echo '<h2>Search by value: [' . $_POST['suggest'] . ']. Did not return any results</h2>';

    if (!empty($users)) { ?>
        <h2>Search result for Users</h2>
        <table class="table">
            <thead class="bg-light">
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Job Role</th>
                <th scope="col">App Role</th>
                <th scope="col">Date In</th>
                <th scope="col">Editing</th>
            </tr>
            </thead>
            <tbody id="data-container">
            <?php foreach ($users as $row) {
                if ($row['id'] != 1) { ?>
                    <tr class="align-middle">
                        <td class="border-end"><?= $row['user_name']; ?></td>
                        <td class="border-end"><?= $row['job_role']; ?></td>
                        <td class="border-end"><?= $row['app_role']; ?></td>
                        <td class="border-end"><?= $row['date_in']; ?></td>
                        <td>
                            <form action="setup?route-page=4" method="post" style="margin:0;">
                                <button type="submit" name="edit" class="btn btn-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm mb-1 mt-1 del-but" data-id="user-<?= $row['id']; ?> ">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php }
            } ?>
            </tbody>
        </table>
        <?php
    }

    if (!empty($tools)) { ?>
        <h2>Search result for Tools</h2>
        <table class="table">
            <thead class="bg-light">
            <tr>
                <th scope="col">Name</th>
                <th scope="col">View</th>
                <th scope="col">Specification</th>
                <th scope="col">ESD</th>
                <th scope="col">Date To QC</th>
                <th scope="col">E/C/D</th>
            </tr>
            </thead>
            <tbody id="data-container">
            <?php foreach ($tools as $row) { ?>
                <tr class="align-middle">
                    <td class="border-end"><?= $row['toolname']; ?></td>
                    <td class="border-end"><img src="<?= $row['image']; ?>" alt="Tool Image Preview" width="100px" height="100px"></td>
                    <td class="border-end"><?= $row['specifications']; ?></td>
                    <td class="border-end"><?= $row['esd']; ?></td>
                    <td class="border-end"><?= $row['exp_date']; ?></td>
                    <td>
                        <form action="setup?route-page=3" method="post" style="margin:0;">
                            <button type="submit" name="edit" class="btn btn-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm mb-1 mt-1 del-but" data-id="tools-<?= $row['id']; ?> ">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    if (!empty($rcards)) { ?>
        <h2>Search result for Rout Actions</h2>
        <table class="table">
            <thead class="bg-light">
            <tr>
                <th scope="col">Stage</th>
                <th scope="col" class="d-flex justify-content-between align-items-center">
                    Actions
                    <span class="btn btn-sm btn-warning" id="lang-switch"><i class="bi bi-translate"></i></span>
                </th>
                <th scope="col">Specification</th>
                <th scope="col">Editing</th>
            </tr>
            </thead>
            <tbody id="data-container">
            <?php foreach ($rcards as $row) { ?>
                <tr class="align-middle">
                    <td class="border-end"><?= $row['sku']; ?></td>
                    <td class="noeng border-end"><?= $row['actions']; ?></td>
                    <td class="eng hidden border-end"><?= $row['actions_eng']; ?></td>
                    <td class="border-end"><?= $row['specifications']; ?></td>
                    <td>
                        <form action="setup?route-page=2" method="post" style="margin:0;">
                            <button type="submit" name="edit" class="btn btn-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm mb-1 mt-1 del-but" data-id="rout-<?= $row['id']; ?> ">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }
    exit("i'am the ghost text ! find me if you can :)");
}
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    echo $timer ?? '';
    ?>
    <style>
        .custom-table thead th,
        .custom-table tbody td {
            display: inline-flex;

        }

        .d-flex {
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
$navBarData['title'] = 'Searching';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>

<div class="main-container">
    <main class="container-fluid content">
        <div class="row">
            <div class="col-12 p-3">
                <form action="" class="form" method="post">
                    <input id="searchThis" type="search" role="searchbox" aria-label="Search" class="form-control" placeholder="Search" required>
                </form>
            </div>
        </div>
        <div id="searchAnswer" class="p-3"></div>
    </main>
</div>
<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm($_GET['route-page'] ?? 1);
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>
</body>
</html>