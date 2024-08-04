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

        .profile-image-placeholder {
            width: 25vw;
            height: 25vw;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .profile-image-placeholder img {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<?php
$localUser = null;
list($action, $title, $saveBtnText, $req) = ['add-new-user', 'creation', 'Save New User', 'required'];
if (isset($_POST['edit'])) {
    $localUser = R::load(USERS, $_POST['edit']);
    list($action, $title, $saveBtnText, $req) = ['update-user-data', 'editing', 'Update This User Information', ''];
}

// NAVIGATION BAR
$navBarData['title'] = 'User ' . $title;
$navBarData['user'] = $user;
$navBarData['page_name'] = $page;
$navBarData['btn_title'] = 'worker';
NavBarContent($navBarData);

/* DISPLAY MESSAGES FROM SYSTEM */
DisplayMessage($args ?? null);
?>
<div class="main-container">
    <main class="container-fluid content">
        <?php if (!isset($_POST['edit']) && !isset($_POST['create'])) { ?>
            <table>
                <thead>
                <tr>
                    <th>N</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Job Role</th>
                    <th>App Role</th>
                    <th>Start Page</th>
                    <th>Last Location</th>
                    <th>Page Mode</th>
                    <th>Date In</th>
                </tr>
                </thead>

                <tbody id="data-container">
                <?php foreach (R::find(USERS) as $row) {
                    if ($row['app_role'] != ROLE_SUPERVISOR && $row['id'] != $user['id']) { ?>
                        <tr class="item-list" data-id="<?= $row['id'] ?>">
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['user_name']; ?></td>
                            <td><?= $row['email']; ?></td>
                            <td><?= $row['phone']; ?></td>
                            <td><?= $row['job_role']; ?></td>
                            <td><?= $row['app_role']; ?></td>
                            <td><?= $row['link']; ?></td>
                            <td><?= $row['last_action']; ?></td>
                            <td><?= $row['preview']; ?></td>
                            <td><?= $row['date_in']; ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        //
        if (isset($_POST['edit']) || isset($_POST['create'])) { ?>
            <div class="row">
                <div class="col-4">
                    <div class="profile-image-placeholder">
                        <img src="/public/images/ips.webp" alt="Profile" id="profile-img">
                    </div>
                </div>


                <div class="col-8">
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $localUser['user_name'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email" value="<?= $localUser['email'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone [optional]</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= $localUser['phone'] ?? '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="jobrole" class="form-label">Job Role</label>
                            <input type="text" class="form-control" id="jobrole" name="jobrole" value="<?= $localUser['job_role'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="work_space" class="form-label">Work Space</label>
                            <select name="work_space" id="work_space" class="form-control" required>
                                <?php
                                $userSpace = !empty($localUser['work_space']) ? $localUser['work_space'] : '';
                                if ($work_space = SR::getAllResourcesInGroup('work_space', true, true)) {
                                    foreach ($work_space as $space) {
                                        $sel = !empty($space) && $space['key_name'] == $userSpace ? 'selected' : '';
                                        ?>
                                        <option value="<?= $space['key_name'] ?>" <?= $sel ?>>
                                            <?= $space['value'] ?>
                                            <bold>
                                                <?= $space['detail'] ?>
                                            </bold>
                                        </option>
                                    <?php }
                                } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">User`s Permissions</label>

                            <?php
                            foreach (ROLE as $role => $label) {
                                if ($role != ROLE_SUPERVISOR) {
                                    ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="approle" id="<?= $role ?>"
                                               value="<?= $role ?>" <?= ($localUser && $localUser['app_role'] == $role) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?= $role ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                <?php }
                            } ?>
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="can-change-data" name="can-change-data"
                                <?= ($localUser && $localUser['can_change_data'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="can-change-data">
                                Can Change Warehouse Data
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="datein" class="form-label">Date in</label>
                            <input type="datetime-local" class="form-control" id="datein" name="date_in"
                                   value="<?= $localUser['date_in'] ?? date('Y-m-d h:i'); ?>" required>
                        </div>

                        <div class="mb-3 eye-box">
                            <label for="user_password" class="form-label">Password</label>
                            <input type="password" class="form-control pi" id="user_password" name="user_password" autocomplete="new-password" <?= $req ?>>
                            <i class="bi bi-eye eye" onclick="tpv()"></i>
                        </div>

                        <div class="mb-3 eye-box">
                            <label for="admin_password" class="form-label">Admin password</label>
                            <input type="password" class="form-control pi" id="admin_password" name="admin_password" value="" <?= $req ?>>
                            <i class="bi bi-eye eye" onclick="tpv()"></i>
                        </div>

                        <div class="mb-2 text-center">
                            <button type="submit" class="btn btn-primary" value="<?= $localUser['id'] ?? ''; ?>" name="<?= $action ?>">
                                <?= $saveBtnText ?>
                            </button>

                            <?php if (isset($_POST['edit'])) { ?>
                                <button type="button" class="btn btn-danger" id="delete_btn" data-id="user-<?= $localUser['id'] ?? ''; ?>">
                                    Delete User [password required!!!]
                                </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>

        <?php } ?>
    </main>
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
