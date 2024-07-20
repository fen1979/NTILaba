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
// NAVIGATION BAR
$navBarData['title'] = 'Route Actions';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'route';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<!-- add new item to list  -->
<form method="post" action="" class="hidden" id="create-form">
    <input type="hidden" name="create">
</form>

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
                <th>Editing</th>
            </tr>
            </thead>

            <tbody id="data-container">
            <?php $table = R::find(ROUTE_ACTION);
            foreach ($table as $row) { ?>
                <tr class="item-list">
                    <td><?= $row['sku']; ?></td>
                    <td class="wrap"><?= $row['actions']; ?></td>
                    <td class="wrap"><?= $row['description']; ?></td>
                    <td><?= $row['specifications']; ?></td>
                    <td>
                        <form method="post" style="margin:0;">
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
    if (isset($_POST['edit']) || isset($_POST['create'])) {
        if (isset($_POST['edit'])) {
            echo '<h2>Edit Rout Action</h2>';
            $routAction = R::load(ROUTE_ACTION, $_POST['edit']);
            $action = 'rout-action-editing';
        }
        if (isset($_POST['create'])) {
            echo '<h2>Create Rout Action</h2>';
            $action = 'rout-action-saving';
            // last number plus one
            $routAction['sku'] = R::count(ROUTE_ACTION) + 1;
        }
        ?>
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

            <button type="submit" class="btn btn-success form-control" name="<?= $action; ?>" value="<?= $routAction['id'] ?? ''; ?>">Save</button>
        </form>
    <?php } ?>
</div>

<?php
// MODAL WINDOW WITH ROUTE FORM
deleteModalRouteForm($_GET['route-page'] ?? 1);
// Футер
footer($page);
// SCRIPTS
ScriptContent($page);
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.doSubmit('#create-btn', '#create-form');
    });
</script>
</body>
</html>
