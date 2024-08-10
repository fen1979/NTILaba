<?php
EnsureUserIsAuthenticated($_SESSION, 'userBean');

$hide_new = isset($_GET['list_id']) && isset($_GET['update']) ? 'hidden' : null;
$list = isset($_GET['list_id']) && isset($_GET['update']) ? R::load(TASK_LIST, $_GET['list_id']) : null;

//Check whether the form is submitted or not
if (isset($_POST['save-new'])) {
    //Get the values from form and save it in variables
    $list_name = $_POST['list_name'];
    $list_description = $_POST['list_description'];
    try {
        $list = R::dispense(TASK_LIST);
        $list->list_name = $list_name;
        $list->list_description = $list_description;
        R::store($list);
        $args = ['info' => 'List Added Successfully', 'color' => 'success'];
        redirectTo('manage-list', $args);
    } catch (Exception $e) {
        $args = ['info' => 'Failed to Add List ' . $e->getMessage(), 'color' => 'danger'];
    }
}

// list updating
if (isset($_POST['update'])) {
    try {
        $list_id = $_POST['update'];
        $list_name = $_POST['list_name'];
        $list_description = $_POST['list_description'];
        $list = R::load(TASK_LIST, $list_id);
        $list->list_name = $list_name;
        $list->list_description = $list_description;
        R::store($list);
        $args = ['info' => 'List Updated Successfully', 'color' => 'success'];
        redirectTo('manage-list', $args);
    } catch (Exception $e) {
        $args = ['info' => 'Failed to Update List ' . $e->getMessage(), 'color' => 'danger'];
    }
}
?>
<form method="POST" action="" <?= $hide_new ?? '' ?>>
    <table class="tbl-half">
        <tr>
            <td>List Name:</td>
            <td><input type="text" name="list_name" placeholder="Type list name here" required="required"/></td>
        </tr>
        <tr>
            <td>List Description:</td>
            <td><textarea name="list_description" placeholder="Type List Description Here"></textarea></td>
        </tr>
        <tr>
            <td>
                <button type="submit" name="save-new" class="btn btn-outline-success">Save new List</button>
            </td>
        </tr>
    </table>
</form>


<form method="POST" action="" <?= empty($hide_new) ? 'hidden' : ''; ?>>
    <table class="tbl-half">
        <tr>
            <td>List Name:</td>
            <td><input type="text" name="list_name" placeholder="Type list name here" value="<?= $list['list_name'] ?? '' ?>" required="required"/></td>
        </tr>
        <tr>
            <td>List Description:</td>
            <td><textarea name="list_description" placeholder="Type List Description Here"><?= trim(($list['list_description'] ?? '')) ?></textarea></td>
        </tr>
        <tr>
            <td>
                <button type="submit" name="update" class="btn btn-outline-success" value="<?= $list['id'] ?? '' ?>">Update this List</button>
            </td>
        </tr>
    </table>
</form>
