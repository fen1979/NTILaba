<div class="row mt-3" style="margin: 0">
    <div class="col-8">
        <?php
        $d = 'disabled';
        if (!empty($project['projectdocs']) && strpos($project['projectdocs'], '.pdf') !== false) {
            $d = '';
            ?>
            <iframe id="pdf-docs" width="100%" height="340%" src="/<?= $project['projectdocs']; ?>"></iframe>
        <?php } else { ?>
            <img src="/<?= getProjectFrontPicture($projectid, 'docs'); ?>" alt="<?= $orderid; ?>"
                 class="img-fluid rounded" style="width: 100%;">
        <?php } ?>
    </div>

    <div class="col-4" style="border-left:solid black 1px;">
        <div class="mb-3">
            <h3 class="mb-3 ps-2">Additional Information</h3>
            <p class="ps-2"> <?= $project['extra']; ?></p>
            <p class="ps-2"><?= 'Project Revision: ' . $project['revision']; ?></p>
            <p class="ps-2 text-primary"><?= 'Created in: ' . $project['date_in']; ?></p>
        </div>
        <div class="mb-3">
            <a role="button" href="<?= BASE_URL . $project->projectdocs; ?>" target="_blanks" class="btn btn-outline-info <?= $d; ?>">
                Open Document
            </a>
        </div>
    </div>
</div>