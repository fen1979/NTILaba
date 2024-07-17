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
        tbody tr{
            cursor: pointer;
        }
        tbody tr:hover {
            color: #ffffff;
            background: #0d6efd;
        }
    </style>
</head>
<body>
<?php
/* NAVIGATION PANEL */
$title = ['title' => 'Users', 'btn-title' => 'worker', 'app_role' => $user['app_role'], 'link' => $user['link']];
NavBarContent($page, $title);
/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<!-- add new item to list  -->
<form method="post" action="" class="hidden" id="create-form">
    <input type="hidden" name="create">
</form>

<div class="main-container">
    <main class="container-fluid content">
        <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
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
                <?php foreach (R::find(USERS) as $row) {
                    if ($row['app_role'] != ROLE_SUPERVISOR) { ?>
                        <tr class="align-middle">
                            <td class="border-end"><?= $row['user_name']; ?></td>
                            <td class="border-end"><?= $row['job_role']; ?></td>
                            <td class="border-end"><?= $row['app_role']; ?></td>
                            <td class="border-end"><?= $row['date_in']; ?></td>
                            <td>
                                <form method="post" style="margin:0;">
                                    <button type="submit" name="edit" class="btn btn-warning btn-sm mb-1 mt-1" value="<?= $row['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm mb-1 mt-1 del-but" data-id="user-<?= $row['id']; ?> ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        if (isset($_POST['edit'])) {
            $user = R::findOne(USERS, 'id = ?', [$_POST['edit']]);
            ?>
            <h2>Edit User Data</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= $user['user_name']; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="jobrole" class="form-label">Job Role</label>
                    <input type="text" class="form-control" id="jobrole" name="jobrole" value="<?= $user['job_role']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">User`s Permissions</label>

                    <?php
                    foreach (ROLE as $role => $label) {
                        if ($role != ROLE_SUPERVISOR) {
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="approle" id="<?= $role ?>"
                                       value="<?= $role ?>" <?= ($user['app_role'] == $role) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?= $role ?>">
                                    <?= $label ?>
                                </label>
                            </div>
                        <?php }
                    } ?>
                </div>

                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="can-change-data" name="can-change-data"
                        <?= $user['can_change_data'] == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="can-change-data">
                        Can Change Warehouse Data
                    </label>
                </div>

                <div class="mb-3">
                    <label for="datein" class="form-label">Date in</label>
                    <input class="form-control" id="datein" name="datein" value="<?= $user['date_in']; ?>" required>
                </div>

                <button type="submit" class="btn btn-primary" value="<?= $user['id'] ?? ''; ?>" name="user-data-editing">Save Changes</button>
            </form>
            <?php
        }

        if (isset($_POST['create'])) {
            ?>
            <div class="card">
                <div class="card-body">
                    <div id="registration-form">
                        <h5 class="card-title text-center mb-4">Adding Worker</h5>

                        <form method="post" id="register-form" autocomplete="off">
                            <div class="mb-3">
                                <label for="regUsername" class="form-label">Name</label>
                                <input type="text" class="form-control" id="regUsername" name="regUserName" required>
                            </div>

                            <div class="mb-3">
                                <label for="regJobRole" class="form-label">Job Role</label>
                                <input type="text" class="form-control" id="regJobRole" name="regJobRole">
                            </div>

                            <div class="mb-3 eye-box">
                                <label for="regUserPassword" class="form-label">Password</label>
                                <input type="password" class="form-control pi" id="regUserPassword" name="regUserPassword" autocomplete="new-password" required>
                                <i class="bi bi-eye eye" onclick="tpv()"></i>
                            </div>

                            <div class="mb-3 eye-box">
                                <label for="adminPassword" class="form-label">Admin password</label>
                                <input type="password" class="form-control pi" id="adminPassword" name="adminPassword" value="" required>
                                <i class="bi bi-eye eye" onclick="tpv()"></i>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">User`s Permissions</label>

                                <?php
                                foreach (ROLE as $role => $label) {
                                    if ($role != ROLE_SUPERVISOR) {
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="approle" id="<?= $role ?>"
                                                   value="<?= $role ?>" <?= (ROLE_WORKER == $role) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="<?= $role ?>">
                                                <?= $label ?>
                                            </label>
                                        </div>
                                    <?php }
                                } ?>
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="can-change-data" name="can-change-data">
                                <label class="form-check-label" for="can-change-data">
                                    Can Change Warehouse Data
                                </label>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-success form-control" id="register" name="add-new-user">Add New Worker</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
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
