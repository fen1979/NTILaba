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
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
<?php
list($action, $title, $saveBtnText) = ['rout-action-saving', 'creation', 'Create Route Action'];
// last number plus one
$routAction['sku'] = R::count(ROUTE_ACTION) + 1;
if (isset($_POST['edit'])) {
    $routAction = R::load(ROUTE_ACTION, $_POST['edit']);
    list($action, $title, $saveBtnText) = ['rout-action-editing', 'editing', 'Update This Route Information'];
    $routAction['sku'] = null;
}
// NAVIGATION BAR
NavBarContent(['title' => 'Route Actions ' . $title, 'user' => $user, 'page_name' => $page, 'btn_title' => 'route']); ?>

<div class="container-fluid">
    <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
        <table>
            <thead>
            <tr>
                <?php $t = 'Part Number, Каталожный номер, מפקת קטלוגית, Stock Keeping Unit'; ?>
                <th><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> SKU</th>
                <th>Actions</th>
                <th>Description</th>
                <th>Specification</th>
            </tr>
            </thead>

            <tbody id="data-container">
            <?php $table = R::find(ROUTE_ACTION);
            foreach ($table as $row) { ?>
                <tr class="item-list" data-id="<?= $row['id'] ?>">
                    <td><?= $row['sku']; ?></td>
                    <td class="wrap"><?= $row['actions']; ?></td>
                    <td class="wrap"><?= $row['description']; ?></td>
                    <td><?= $row['specifications']; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    // создание или обновление рут карты
    if (isset($_POST['edit']) || isset($_POST['create'])) { ?>
        <form method="post" class="mb-5 mt-3">
            <div class="mb-3">
                <?php $t = 'Part Number, Каталожный номер, מפקת קטלוגית, Stock Keeping Unit'; ?>
                <label for="sku" class="form-label"><i class="bi bi-info-circle" data-title="<?= $t; ?>"></i> SKU <b class="text-danger">*</b></label>
                <input type="text" class="form-control" id="sku" name="sku" value="<?= $routAction['sku'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="actions" class="form-label">Actions <b class="text-danger">*</b></label>
                <textarea type="text" class="form-control" id="actions" name="actions" required><?= $routAction['actions'] ?? ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea type="text" class="form-control" id="description" name="description"
                          placeholder="Optional"><?= $routAction['description'] ?? ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="specifications" class="form-label">Specifications</label>
                <input class="form-control" id="specifications" name="specifications" value="<?= $routAction['specifications'] ?? ''; ?>">
            </div>

            <div class="mb-2 text-center">
                <button type="submit" class="btn btn-success" name="<?= $action; ?>" value="<?= $routAction['id'] ?? ''; ?>">
                    <?= $saveBtnText ?>
                </button>

                <?php if (isset($_POST['edit'])) { ?>
                    <button type="button" class="btn btn-danger" id="delete_btn" data-id="rout-<?= $routAction['id'] ?? ''; ?>">
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
// Футер // SCRIPTS
PAGE_FOOTER($page); ?>
</body>
</html>
