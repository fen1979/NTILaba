<!doctype html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>
    <?php
    /* ICON, TITLE, STYLES AND META TAGS */
    HeadContent($page);
    ?>
    <style>
        .custom-table thead th,
        .custom-table tbody td {
            display: inline-flex;

        }

    </style>
</head>
<body>
<?php
// NAVIGATION BAR
$navBarData['title'] = 'Projects';
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'project';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<!-- add new item to list  -->
<form method="post" action="/new_project" class="hidden" id="create-form">
    <input type="hidden" name="create">
</form>

<div class="main-container">
    <main class="container-fluid content">
        <h2>Projects List</h2>

        <table class="table">
            <thead class="bg-light">
            <tr class="bg-light">
                <th scope="col">Project Name</th>
                <th scope="col">Customer Name</th>
                <th scope="col">Project Actions</th>
            </tr>
            </thead>

            <tbody id="data-container">
            <?php $table = R::find(PROJECTS, 'ORDER BY customername ASC');
            foreach ($table as $row) {
                $href = PDF_FOLDER . 'routes.php?projectid=' . $row['id'];
                ?>
                <tr class="align-middle">
                    <td class="border-end"><?= str_replace('_', ' ', $row['projectname']); ?></td>
                    <td class="border-end"><?= $row['customername']; ?></td>
                    <td>
                        <form method="post" style="margin:0;">
                            <a role="button" class="btn btn-outline-info btn-sm mb-1 mt-1" href="<?= $href; ?>" target="_blank">
                                <i class="bi bi-list-columns"></i>
                            </a>
                            <button type="submit" name="topdf" class="btn btn-outline-primary btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-filetype-pdf"></i>
                            </button>
                            <button type="submit" name="printrout" class="btn btn-outline-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button type="submit" name="inform" class="btn btn-outline-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="submit" name="edit" class="btn btn-outline-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm mb-1 mt-1 del-but" data-id="user-<?= $row['id']; ?> ">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>

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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        dom.doSubmit('#create-btn', '#create-form');
    });
</script>
</body>
</html>
