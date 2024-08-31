<?php if (!empty($project['tools']) && $project['tools'] != 'NC') { ?>
    <table class="p-3">
        <!-- header -->
        <thead>
        <tr>
            <?php
            /* настройки вывода от пользователя */
            if ($settings = getUserSettings($user, TOOLS)) {
                foreach ($settings as $item => $_) {
                    echo '<th>' . SR::getResourceValue(TOOLS, $item) . '</th>';
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
        $toolsId = explode(',', $project['tools']);
        foreach ($toolsId as $id) {
            $row = R::load(TOOLS, $id);
            echo '<tr class="item-list">';
            if ($settings) {
                foreach ($settings as $item => $_) {
                    if ($item != 'image') {
                        if ($item == 'responsible')
                            echo '<td>' . (json_decode($row[$item])->name) . '</td>';
                        else
                            echo '<td>' . $row[$item] . '</td>';
                    } else {
                        echo '<td>' .
                            '<img src="/' . (!empty($row['image']) ? $row['image'] : 'public/images/pna_en.webp') .
                            '" alt="Tool Image Preview" width="180px" >' .
                            '</td>';
                    }
                }
            }
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
<?php } else { ?>
    <!-- notice for creation tools table for project -->
    <div class="row mt-3">
        <div class="col-12">
            <h3>No tool has to be selected for this project yet.</h3>
            <br>
            <?php $vurl = "new_project?mode=editmode&pid={$project['id']}&back-id={$_GET['orid']}"; ?>
            <button type="button" value="<?= $vurl; ?>" class="url btn btn-outline-primary">
                Do you want to select tools?
            </button>
        </div>
    </div>
<?php } ?>