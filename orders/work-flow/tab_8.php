<?php
/*!
 * здесь собран код вывода для работы над проектами в Заказе
 * вкладка номер 8 (tab8)
 *
 * выводит шаги для работы над всеми проектами которые не являются СМТ сборкой
 * так же включая сборку ТН компонентов в платы
 *
 */

// STANDARD PROJECT ASSEMBLY TYPE
?>
<style>
    .image-container {
        position: relative;
        display: inline-block;
    }

    .magnifier {
        position: absolute;
        border: 1px solid #000;
        width: 350px;
        height: 350px;
        overflow: hidden;
        display: none;
        pointer-events: none;
        border-radius: 50%;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .magnifier img {
        position: absolute;
        width: auto;
        height: auto;
        max-width: none;
    }
</style>
<div class="step-box mt-3">
    <?php if ($order->status == 'st-8' && $assy_in_progress) {

        foreach ($stepsData as $step) {
            /* если данный шаг равен записаному в заказе то выводим его */
            if ($step['id'] == $assy_in_progress->current_stepid) {
                echo ($step['validation']) ? '<p class="text-white bg-danger">' . $step['validation'] . '</p>' : '';
                ?>
                <div class="row">
                    <div class="col-8">
                        <img class="step-image rounded magnify-image" src="/<?= $step['image']; ?>" alt="Hello asshole">
                        <?php if ($step['video'] != 'none') { ?>
                            <video src="<?= $step['video']; ?>" controls width="100%" height="auto">
                                Your browser not support video
                            </video>
                        <?php } ?>
                    </div>

                    <div class="col-4 border-start">
                        <!-- step description -->
                        <div class="mb-5 border p-2">
                            <h5 class="mb-3">Step Number: <?= $step['step']; ?></h5>
                            <small>Assembly instructions</small>
                            <p class="text-primary"><?= $step['description']; ?></p>
                            <small>Rout Action</small>
                            <p class="text-primary"><?= $step['routaction'] ?? 'Route Action not available'; ?></p>
                        </div>

                        <form action="" method="post">
                            <!-- step actions form -->
                            <div class="border-top mb-2"></div>

                            <div class="mb-2">
                                <?php $t = 'The number of parts produced for a given order according to the project step. 
                                            This field is required for any operation! If there are no manufactured parts, set the value to ZERO!
                                            Order Required Amount writen by default!'; ?>
                                <label for="qty_done_for_step" class="form-label">
                                    <i class="bi bi-info-circle text-info fs-4" data-title="<?= $t; ?>"></i>&nbsp;
                                    QTY for this step complited &nbsp;
                                    <b class="text-danger">*</b>

                                </label>
                                <input type="hidden" name="assy_step_id" value="<?= $assy_in_progress->id; ?>">
                                <input type="number" name="qty_done" id="qty_done_for_step" required class="form-control" value="<?= $amount; ?>">
                            </div>

                            <div class="border-top mb-5"></div>

                            <!-- button next step ang back to previos step -->
                            <div class="mb-2 border rounded p-2">
                                <small>
                                    After completing work on this step, click on the <b>“Step completed, go to next”</b> button.
                                    If an error occurred and the next button was pressed!
                                    but the work on the step was not completed!
                                    Click on the <b>"Oops, my mistake! Go back"</b> button to return to the previous step.
                                </small>
                            </div>
                            <div class="mb-2">
                                <?php
                                if ($step['validation'] == 0) {
                                    /* кнопка перехода на следующий шаг */
                                    $goToNext = $step['id'] + 1;
                                    ?>
                                    <button type="submit" name="next_step" value="<?= $goToNext; ?>" class="url btn btn-outline-success">
                                        Step completed, go to next
                                    </button>
                                    <?php
                                } else {
                                    /* кнопка вызова проверяющего */
                                    ?>
                                    <button type="submit" name="validate_step" value="<?= $step['id']; ?>" class="url btn btn-outline-danger">
                                        Click to call an inspector
                                    </button>
                                    <?php
                                }

                                /* кнопка возврата к предыдущему шагу если что */
                                if ($step['step'] != 1) {
                                    $goBack = $step['id'] - 1; ?>
                                    <button type="submit" name="back_to_previos" value="<?= $goBack; ?>" class="url btn btn-outline-warning">
                                        Oops, my mistake! Go back
                                    </button>
                                <?php } ?>
                            </div>

                            <!-- если над проектом работают более одного человека то выводим кнопку пропустить шаг -->
                            <?php
                            $workers = explode(',', $order->workers);
                            if (count($workers) > 1) {
                                ?>
                                <div class="mb-2 border rounded p-2">
                                    <small>
                                        <b class="text-warning">NOTICE!</b><br>
                                        If you are not the only person working on this project and did not complete this step yourself,
                                        please click the <b>“Skip this Step”</b> button.
                                        If you accidentally skipped a step, click the <b>"Oops, my mistake! Go back"</b>
                                        button to return to the previous step.
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <?php $skipStep = $step['id']; ?>
                                    <button type="submit" name="skip_this_step" value="<?= $skipStep; ?>" class="url btn btn-outline-info">
                                        Skip this Step
                                    </button>
                                </div>

                                <div class="mb-2 border rounded p-2">
                                    <small>
                                        <b class="text-warning">NOTICE!</b><br>
                                        If for some reason the order needs to be paused,
                                        you will need to indicate the reason why the order was put on hold.
                                        For this action, click on the <b>“Set Order On Pause”</b> button.
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <button type="submit" name="set_on_pause" value="<?= $order->id; ?>" class="url btn btn-outline-danger">
                                        Set Order On Pause
                                    </button>
                                </div>
                            <?php } ?>
                        </form>

                        <!-- forward step to user -->
                        <form action="" method="POST" id="forward_step">
                            <div class="mb-2 border rounded p-2">
                                <small>
                                    <b class="text-danger">WARNING!</b><br>
                                    Transferring a step to another employee will entail a number of changes in
                                    the documentation for the execution of order steps!
                                    Your progress will be deleted, to select a person, click on <b>“Forward Step To User”</b> button.
                                </small>
                            </div>

                            <input type="hidden" name="step_id" value="<?= $step['id']; ?>">
                            <input type="hidden" name="assy_step_id" value="<?= $assy_in_progress->id; ?>">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle fs-5 btn-outline-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Forward Step To User
                                </button>
                                <ul class="dropdown-menu ps-2 pe-2">
                                    <?php
                                    $allUsers = R::find(USERS);
                                    foreach ($allUsers as $key => $u) {
                                        if ($u['id'] != '1') {
                                            ?>
                                            <div class="radio-item">
                                                <input type="radio" name="workers" id="in-<?= $key; ?>" value="<?= $u['id']; ?>" class="me-2">
                                                <label for="in-<?= $key; ?>" style="width: 75%;"> <?= $u['user_name']; ?></label>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <li>
                                        <button class="btn btn-outline-success form-control" type="submit" name="forward_step_to_user">
                                            Forward step
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
                <?php

                break;
            } // if step = assy flow step now
        } // foreach

    } elseif ($order->status == 'st-8') {
        // if order status = st-8
        ?>
        <h3>
            To continue assembling the project for this order,
            you need to go to the "Project Steps" tab and select the required step to work on!
        </h3>
    <?php } else echo '<h3 class="mt-3">The status of this order does not allow you to start working on the order!</h3>'; ?>

    <div class="magnifier"></div>
</div>

<script>
    const magnifier = document.querySelector('.magnifier');
    const img = document.querySelector('.magnify-image');
    const magnifyImg = document.createElement('img');
    magnifyImg.src = img.src;
    magnifier.appendChild(magnifyImg);

    // Устанавливаем увеличенные размеры
    const zoomFactor = 2;
    magnifyImg.style.width = `${img.width * zoomFactor}px`;
    magnifyImg.style.height = `${img.height * zoomFactor}px`;

    img.addEventListener('mousemove', function(e) {
        const imgRect = img.getBoundingClientRect();
        const magnifierRect = magnifier.getBoundingClientRect();
        const x = e.clientX - imgRect.left;
        const y = e.clientY - imgRect.top;

        if (x > 0 && x < img.width && y > 0 && y < img.height) {
            magnifier.style.display = 'block';
            magnifier.style.left = `${e.pageX - magnifierRect.width / 2}px`;
            magnifier.style.top = `${e.pageY - magnifierRect.height / 2}px`;

            // Рассчитываем положение изображения внутри увеличительного стекла
            let rx = (x / imgRect.width * magnifyImg.width) - magnifierRect.width / 2;
            let ry = (y / imgRect.height * magnifyImg.height) - magnifierRect.height / 2;

            magnifyImg.style.left = `-${rx}px`;
            magnifyImg.style.top = `-${ry}px`;

            // Проверка на приближение к краям страницы
            if (e.pageX + magnifierRect.width / 2 > window.innerWidth) {
                magnifier.style.left = `${window.innerWidth - magnifierRect.width}px`;
            }
            if (e.pageY + magnifierRect.height / 2 > window.innerHeight) {
                magnifier.style.top = `${window.innerHeight - magnifierRect.height}px`;
            }
            if (e.pageX - magnifierRect.width / 2 < 0) {
                magnifier.style.left = `0px`;
            }
            if (e.pageY - magnifierRect.height / 2 < 0) {
                magnifier.style.top = `0px`;
            }
        } else {
            magnifier.style.display = 'none';
        }
    });

    img.addEventListener('mouseleave', function() {
        magnifier.style.display = 'none';
    });
</script>