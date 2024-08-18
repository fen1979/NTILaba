<?php
if (!empty($_GET['shared'])) {
    $linkToView = $_GET['shared'];
    $project = R::findOne(PRODUCT_UNIT, 'sharelink = ?', [$linkToView]);
    $projectData = R::findAll(PROJECT_STEPS, "projects_id LIKE ?", [$project->id]);
}
?>
<!DOCTYPE html>
<html lang="<?= LANG; ?>" <?= VIEW_MODE; ?>>
<head>

    <?php HeadContent('shared'); ?>
</head>
<body>
<header style="height: 6rem;">
    <form action="" id="routing" class="hidden" method="post"></form>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top navbar-scroll blury">
        <div class="container-fluid">
            <!-- TITLE -->
            <h3 class="navbar-brand">Project: <?= $project['projectname'] ?? 'No Name'; ?></h3>
            <!-- GAMBURGER BUTTON -->
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse"
                    data-mdb-target="#navBarContent" aria-controls="navBarContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon d-flex justify-content-start align-items-center"></span>
            </button>
            <!-- NAVIGATION PANEL BUTTONS -->
            <div class="collapse navbar-collapse" id="navBarContent" style="flex-grow: 0;">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <button type="button" class="url btn btn-sm btn-outline-danger" value="">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<?php if ($projectData): ?>
<div class="container-fluid mt-4">
    <div class="row">
        <?php
        foreach ($projectData as $key => $value) :
            $imgPath = $value['image'];
            $videoPath = (strpos($value['video'], '.mp4') !== false) ? $value['video'] : 'none';
            $projectId = $value['projects_id'];
            $stepIdent = $value['identify'] ?? '';
            $description = $value['description'];
            $newStepNumber = $value['step'];
            $revision = $value['revision'];

            /* getting checkbox value */
            $chkbx = $value['mustbeapproved'];
            $opacity = ($chkbx) ? '' : 'style="opacity:0;"';
            ?>
            <div class="mb-4 col-12">
                <div class="card shadow-sm">
                    <!--  check box  -->
                    <span class="text-danger ms-2 mt-2 mb-2" <?= $opacity; ?>>
                        <i class="bbi bi-check2-square"></i>
                        &nbsp; Must Be Approved!
                    </span>

                    <!-- фотография -->
                    <img src="<?= '/' . $imgPath; ?>" alt="<?= $projectId; ?>">
                    <!-- video -->
                    <?php if ($videoPath != 'none') { ?>
                        <video src="<?= '/' . $videoPath; ?>" controls class="video-vs h" width="640" height="480">
                            Your browser isn't support video!
                        </video>
                    <?php } ?>
                    <div class="card-body">
                        <!-- описание к фотографии -->
                        <h3 class="card-text"><?= $description; ?></h3>

                        <div class="d-flex justify-content-between align-items-center">

                            <!-- шаг данного этапа -->

                            <b>
                                <button type="button" class="btn btn-outline revision-history">
                                    <i class="bi bi-eye"></i>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <small class="text-warning">Version&nbsp;<?= $revision; ?></small>
                                </button>
                            </b>
                            <b><small class="text-danger">Step №<?= $newStepNumber; ?></small></b>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        endforeach;
        endif;
        ?>
    </div>
</div>
<!-- JAVASCRIPTS -->
<?php ScriptContent('shared'); ?>
</body>
</html>

