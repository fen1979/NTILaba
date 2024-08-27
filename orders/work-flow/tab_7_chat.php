<div class="row" style="margin: 0">
    <!-- chat messages window -->
    <div class="col-9">
        <div id="chatWindow" style="overflow: scroll; height: 65vh;">
            <?php
            $ext_arr = ['zip', 'rar', '7z'];
            foreach ($orderChat as $msg) {
                $edited = ($msg->edited) ? 'darker' : '';
                $f_disp = !empty($f = $msg['file_path']);
                $if_disp = !empty($if = $msg['image_file_path']);
                $vf_disp = !empty($vf = $msg['video_file_path']);
                $af_disp = !empty($af = $msg['audio_file_path']);
                /* сообщение с файлом видео или картинкой */
                if ($f) {
                    $f_ext = strtolower(pathinfo(basename($f), PATHINFO_EXTENSION));
                    $icon_style = (in_array($f_ext, $ext_arr)) ? "file-zip" : "filetype-$f_ext";
                } ?>
                <div class="container <?= $edited; ?>">
                    <span><?= $msg['user_name']; ?></span>

                    <?php if ($if_disp): ?>
                        <img src="/<?= $if; ?>" alt="Avatar" class="right">
                    <?php endif; ?>

                    <?php if ($vf_disp): ?>
                        <video controls class="right">
                            <source src='/<?= $vf; ?>' type='video/mp4'>
                            <!--Your browser does not support the video tag.-->
                            Go to hell video not sponsored by me!
                        </video>
                    <?php endif; ?>

                    <?php if ($af_disp): ?>
                        <audio controls class="right">
                            <source src='/<?= $af; ?>' type='audio/m4a'>
                            <source src='/<?= $af; ?>' type='audio/mp3'>
                            <!--Your browser does not support the audio element.-->
                            Go to hell audio not sponsored by me!
                        </audio>
                    <?php endif;

                    if ($f_disp) {
                        if ($f_ext != 'pdf') { ?>
                            <a href="/<?= $f; ?>" class="btn btn-primary right" download>
                                <i class="bi bi-<?= $icon_style; ?>"></i> Download
                            </a>
                        <?php } else { ?>
                            <a href="/<?= $f; ?>" class="btn btn-primary right" target="_blank">
                                <i class="bi bi-<?= $icon_style; ?>"></i> Preview
                            </a>
                        <?php }
                    } ?>
                    <p data-msgid="<?= $msg->id; ?>" class="msg-url"> <?= $msg['message']; ?></p>
                    <span class="time-left"><?= $msg['date_in']; ?></span>
                    <?php if ($f_disp) { ?>
                        <span class="time-left"><?= basename($f); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <div class="gear-container" id="gear-container">
            <i class="bi bi-gear-wide-connected gear" id="gear1"></i>
            <i class="bi bi-gear-wide-connected gear" id="gear2"></i>
        </div>
        <form action="" method="post" class="mt-4" enctype="multipart/form-data">
            <div class="d-flex">
                <?php $t = 'A message written here can be edited or deleted only within 15 minutes from the moment it was saved!'; ?>
                <textarea name="messageText" id="chatTextArea" class="form-control" style="flex-grow: 1; max-width: 75%;" rows="3"
                          placeholder="<?= $t; ?>" required></textarea>

                <div class="d-flex flex-row justify-content-between">
                    <input type="file" name="chatFile" class="hidden" id="fileToTake">
                    <!-- write and send audio message -->
                    <button id="recordButton" type="button" class="btn btn-outline-primary ms-2 btn-rounded">
                        <i class="bi bi-mic-fill fs-3"></i>
                    </button>
                    <!-- add file to message -->
                    <button type="button" id="getFileContent" class="btn btn-outline-primary ms-2 btn-rounded">
                        <i class="bi bi-folder-plus fs-2"></i>
                    </button>
                    <!-- send message btn -->
                    <button type="submit" name="save-message" id="chat-send-button" class="btn btn-outline-info ms-2 btn-rounded">
                        <i class="bi bi-send fs-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- chat rules notice and file name preview -->
    <div class="col-3 border-start">
        <i class="bi bi-info-circle text-info"></i>
        <p class="chat-notes">
            <b>NOTICE</b>
            <br>
            <b class="info">Audio Recording:</b> <i class="bi bi-mic-fill info"></i>
            <br>
            Press and hold the record button to create a voice message.
            <br>
            After recording the audio, make sure to write a note on the recording.
            <br>
            After sending the message, please be patient! Saving recordings and files takes time, which depends on your Internet connection.
            <br>
            <b class="info">File Uploading:</b>
            <br>
            Only jpg, png, webp, mp4, pdf, csv, xls, xlsx, doc, txt, zip, rar, 7z can be uploaded!
            <br>
            Max size 300MB!!!
            <br>
            <b class="info">Message Edit/Delete:</b>
            <br>
            <?= $t; ?>
        </p>
        <br>
        <br>
        <!-- file name preview -->
        <div id="fileNamePreviewContainer" class="p-2 hidden warning rounded">
            <h3>Choosen file:</h3>
            <p class="text-primary" id="fileNamePreview"></p>
        </div>
    </div>
</div>