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
/* NAVIGATION PANEL */
$title = ['title' => 'Warehouse Types', 'btn-title' => 'type', 'app_role' => $user['app_role'], 'link' => $user['link']];
NavBarContent($page, $title);
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
                <th>Warehouse Type</th>
                <th>Description</th>
                <th>Editing</th>
            </tr>
            </thead>

            <tbody id="data-container">
            <?php foreach (R::find(WH_TYPES) as $row) { ?>
                <tr class="item-list">
                    <td class="wrap"><?= $row['type_name']; ?></td>
                    <td class="wrap"><?= $row['description']; ?></td>
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
            echo '<h2>Edit Warehouse Type</h2>';
            $warehouseType = R::load(WH_TYPES, $_POST['edit']);
            $action = 'wh-action-editing';
        }
        if (isset($_POST['create'])) {
            echo '<h2>Create Warehouse</h2>';
            $action = 'wh-action-saving';
        }
        ?>
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

            <button type="submit" class="btn btn-success form-control" name="<?= $action; ?>" value="<?= $warehouseType['id'] ?? ''; ?>">Save</button>
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
