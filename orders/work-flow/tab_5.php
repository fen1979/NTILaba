<?php if ($projectBom) { ?>
    <table class="p-3">
        <!-- header -->
        <thead>
        <tr>
            <?php
            if ($settings = getUserSettings($user, PROJECT_BOM)) {
                foreach ($settings as $item => $_) {
                    echo '<th>' . SR::getResourceValue(PROJECT_BOM, $item) . '</th>';
                }
            } else {
                ?>
                <th>
                    Your view settings for this table isn't exist yet
                    <a role="button" href="/setup" class="btn btn-outline-info">Edit Columns view settings</a>
                </th>
            <?php } ?>
        </tr>
        </thead>
        <!-- table -->
        <tbody>
        <?php
        foreach ($projectBom as $line) {
            echo '<tr class="item-list">';
            if ($settings) {
                foreach ($settings as $item => $_) {
                    echo '<td>' . $line[$item] . '</td>';
                }
            }
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
    <?php
} else {
    $_SESSION['projectid'] = $project->id;
    ?>
    <div class="align-middle row mt-3">
        <div class="col-12">
            <h3>Information on the available parts to create this project has not yet to be entered!</h3>
            <br>
            <?php $url = "check_part_list?mode=editmode&back-id=$order->id&pid=$project->id"; ?>
            <button type="button" value="<?= $url; ?>" class="url btn btn-outline-primary">
                Do you want to enter information?
            </button>
        </div>
    </div>
    <?php } ?>