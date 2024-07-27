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
            white-space: nowrap;
            cursor: pointer;
        }

        table thead tr th {
            /* Important */
            position: sticky;
            z-index: 100;
            top: 6.5%;
        }

        th:last-child, td:last-child {
            text-align: right;
            padding-right: 1rem;
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

        .wrap {
            white-space: wrap;
        }
    </style>
</head>
<body>
<?php
list($action, $title, $saveBtnText) = ['wh-action-saving', 'creation', 'Create Route Action'];
if (isset($_POST['edit'])) {
    $warehouseType = R::load(WH_TYPES, $_POST['edit']);
    list($action, $title, $saveBtnText) = ['wh-action-editing', 'editing', 'Update This Route Information'];
}

// NAVIGATION BAR
$navBarData['title'] = 'Warehouse Types '. $title;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'type';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="container-fluid">
    <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
        <table>
            <thead>
            <tr>
                <th>Warehouse Type</th>
                <th>Description</th>
            </tr>
            </thead>

            <tbody id="data-container">
            <?php foreach (R::find(WH_TYPES) as $row) { ?>
                <tr class="item-list" data-id="<?= $row['id'] ?>">
                    <td class="wrap"><?= $row['type_name']; ?></td>
                    <td class="wrap"><?= $row['description']; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }
    if (isset($_POST['edit']) || isset($_POST['create'])) { ?>
        <form method="post" class="mb-5 mt-3">
            <div class="mb-3">
                <label for="type_name" class="form-label">Type Name <b class="text-danger">*</b></label>
                <input type="text" class="form-control" id="type_name" name="type_name" value="<?= $warehouseType['type_name'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea type="text" class="form-control" id="description" name="description"
                          placeholder="Optional"><?= $warehouseType['description'] ?? ''; ?></textarea>
            </div>

            <div class="mb-2 text-center">
                <button type="submit" class="btn btn-success" name="<?= $action; ?>" value="<?= $warehouseType['id'] ?? ''; ?>">
                    <?= $saveBtnText ?>
                </button>

                <?php if (isset($_POST['edit'])) { ?>
                    <button type="button" class="btn btn-danger" id="delete_btn" data-id="whtype-<?= $warehouseType['id'] ?? ''; ?>">
                        Delete Route Act [password required!!!]
                    </button>
                <?php } ?>
            </div>
        </form>
    <?php } ?>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm();
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>
</body>
</html>
