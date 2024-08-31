<?php
$user = EnsureUserIsAuthenticated($_SESSION, 'userBean');
$page = 'resources';
list($btn_name, $btn_text) = ['add-new-resource', 'Save Resource'];

if (isset($_POST['add-new-resource'])) {
    if (SR::addResource(_E($_POST['group_name']), _E($_POST['key_name']), _E($_POST['value']), _E($_POST['detail'])))
        redirectTo('resources');
}

if (isset($_POST['update-resource'])) {
    SR::updateResource(_E($_POST['group_name']), _E($_POST['key_name']), _E($_POST['value']), _E($_POST['detail']));
}

if (isset($_POST['change-group-name'])) {
    $r = 'l';
}

if (isset($_POST['change-key-name'])) {
    $r = 'j';
}

if (isset($_GET['resid'])) {
    list($btn_name, $btn_text) = ['update-resource', 'Update Resource Value & Detail'];
    $reso = R::load('resources', _E($_GET['resid']));
}
?>
<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .item-list:hover {
            background: #0d6efd;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: normal;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 4.5em;
        }

        th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #717171;
            color: #ffffff;
        }

        .sticky-form form {
            position: sticky;
            top: 4.5em;
            z-index: 200;
            background-color: white;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<?php
// NAVIGATION BAR
NavBarContent(['active_btn' => Y['SETTINGS'], 'user' => $user, 'page_name' => $page]); ?>

<div class="container-fluid content">
    <div class="row">
        <div class="col-3 sticky-form">
            <form action="" method="post">
                <h4>Site Resources</h4>
                <div class="mb-3">
                    <label for="group_name" class="form-label">Group Name</label>
                    <input type="text" name="group_name" id="group_name" placeholder="Group Name" value="<?= $reso['group_name'] ?? '' ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="key_name" class="form-label">Key Name</label>
                    <input type="text" name="key_name" id="key_name" placeholder="Key Name" value="<?= $reso['key_name'] ?? '' ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="value" class="form-label">Value [text, command]</label>
                    <input type="text" name="value" id="value" placeholder="Value" value="<?= $reso['value'] ?? '' ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="detail" class="form-label">Detail \ Status \ Color Etc...</label>
                    <input type="text" name="detail" id="detail" placeholder="Changeble value" value="<?= $reso['detail'] ?? '' ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <button type="submit" name="<?= $btn_name ?>" class="form-control btn btn-outline-proma"><?= $btn_text ?></button>
                </div>

                <?php if ($btn_name == 'update-resource'): ?>
                    <div class="mb-3">
                        <button type="submit" name="change-group-name" class="form-control btn btn-outline-proma">Change Resource Group Name</button>
                    </div>

                    <div class="mb-3">
                        <button type="submit" name="change-key-name" class="form-control btn btn-outline-proma">Change Resource Key Name</button>
                    </div>

                    <div class="mb-3">
                        <button type="button" id="delete-resource" class="form-control btn btn-outline-danger">Delete This Resource</button>
                    </div>

                    <div class="mb-3">
                        <button type="button" id="delete-group-resource" class="form-control btn btn-outline-danger">Delete Resource Group</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-9">
            <table id="table">
                <thead>
                <tr>
                    <th>Group</th>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Detail</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (SR::getAllResources() as $res) { ?>
                    <tr class="item-list" data-id="<?= $res['id'] ?>" id="row-<?= $res['id'] ?>">
                        <td><?= $res['group_name'] ?></td>
                        <td><?= $res['key_name'] ?></td>
                        <td><?= $res['value'] ?></td>
                        <td><?= $res['detail'] ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// MODAL DIALOG FOR VIEW RESPONCE FROM SERVER IF SEARCHED VALUE EXIST
SearchResponceModalDialog($page, 'search-responce');

/* SCRIPTS */
PAGE_FOOTER($page); ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
// Выбираем таблицу с id searchAnswer
        const tBody = document.getElementById('table');

        // Добавляем делегированный обработчик событий на таблицу
        tBody.addEventListener('click', function (event) {
            // Находим родительский <tr> элемент
            let row = event.target;
            while (row && row.tagName.toLowerCase() !== 'tr') {
                row = row.parentElement;
            }

            // Если <tr> элемент найден и у него есть data-id
            if (row && row.dataset.id) {
                // Получаем значение data-id
                const dataId = row.dataset.id;
                //const dataPage = row.dataset.page;
                let newUrl;

                //if (dataPage) {
                newUrl = "/resources?resid=" + dataId + "&#row-" + (dataId);
                // } else {
                //     newUrl = "/test?resid=" + dataId;
                // }

                // Переход по указанному адресу
                window.location.href = newUrl;
            }
        });
    });
</script>
</body>
</html>
