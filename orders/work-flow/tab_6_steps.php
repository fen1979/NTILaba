<div class="step-box mt-3">
    <?php
    if ($stepsData) {
        $stepCount = 0;
        /* выводим все шаги для просмотра и выбора в работу */
        foreach ($stepsData as $step) {
            // проверяем если шаг был завершен то не выводим его
            if (!Orders::isStepComplite($order->status, $step['id'])) {
                $stepCount++;
                echo ($step['validation']) ? '<p class="text-white bg-danger">' . $step['validation'] . '</p>' : '';
                ?>
                <div class="row row-side" id="sid-<?= $step['step']; ?>" style="margin: 0">
                    <div class="col-5">

                        <?php
                        // на случай если в проекте нет шагов с фото или видео
                        if (!empty($step['image'])) {
                            echo '<img class="step-image" src="/' . $step['image'] . '" alt="Hello asshole">';
                        } else {
                            echo '<h3>' . $step['routeaction'] . '</h3>';
                        }

                        if ($step['video'] != 'none') {
                            echo '<video src="/' . $step['video'] . '" controls width="100%" height="auto">';
                            echo 'Your browser not support video';
                            echo '</video>';
                        }
                        ?>
                    </div>

                    <div class="col-7 info-side">
                        <h5 class="mb-3">Step Number: <?= $step['step']; ?></h5>
                        <p class="text-primary"><?= $step['description']; ?></p>
                        <pre class="warning rounded border p-2">
WARNING!
Before you start this step, read the rules for transitioning between steps!
1) Execute step:
After completing the step completely.
IMPORTANT!
Click on the "step completed" button
This will prevent the possibility of taking a step into work by mistake!
2) Partial execution:
In case of partial or serial execution of the order.
IMPORTANT!
After completing the step, click on the “next step” button.
This button will appear if a serial number is included in the order!
3) Transferring a step to another worker:
If you need to transfer a step to another worker.
IMPORTANT!
Select an employee from the list and click the “transfer step” button.
This action will open up the opportunity for another worker to choose a step to work on!
4) Step verification by administrator:
If this step is verified, the “request step verification” button will be presented on the page.
IMPORTANT!
Click on this button after making the first copy of the product in your order!
If serial numbering is set, the action is performed for all copies of the product at this step!
5) Stop order fulfillment:
In a situation where a stop is required while executing an order.
IMPORTANT!
Press the "order to pause" button
Next, in the dialog that opens, you need to write the reason for stopping the order in any language
and click the “ok” button to complete the operation.
                                </pre>
                        <?php
                        // если пользователь взял в работу один шаг то отключаем возможность взять другой в работу
                        if (!$assy_in_progress && $order->status == 'st-8') { ?>
                            <form action="" method="post">
                                <?php $assy_work_flow = R::findOne(ASSY_PROGRESS, 'current_stepid = ?', [$step['id']]); ?>
                                <input type="hidden" name="assyid" value="<?= $assy_work_flow->id; ?>">
                                <input type="hidden" name="stepid" value="<?= $step['id']; ?>">
                                <button type="submit" class="btn btn-outline-primary" name="take-a-step-to-work">
                                    Take a step to work
                                </button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php }
        }
        // завершение заказа или повтор если требуется серийный номер или поштучное изготовление
        if ($stepCount == 0) {
            ?>
            <div class="mb-3 mt-3 p3 text-center">
                <h3>All project steps have been completed, complete the order or repeat all steps.</h3>
                <form action="" method="post">
                    <button type="submit" name="complete_order" value="<?= $order->id; ?>" class="btn btn-outline-dark">
                        Order assembly complete, move on to the next order?
                    </button>

                    <h4>For orders where a serial number is required.</h4>
                    <input type="text" name="serial_number_for_assy_flow" class="form-control" placeholder="Write next serial number">
                    <button type="submit" name="repite_order" value="<?= $order->id; ?>" class="btn btn-outline-dark">
                        Repeat the assembly procedure step by step for the new serial number
                    </button>
                </form>
            </div>
            <?php
        }

        // если нет шагов по сборке, выводим предложение добавить шаги в проекты
    } else { ?>
        <div class="mb-3">
            <h4>It seems there are no assembly instructions for this project yet. Would you like to add assembly instructions to this project?</h4>
            <a role="button" href="/add_step?pid=<?= $project->id; ?>" target="_blank" class="btn btn-outline-info">
                Add Project steps
            </a>
        </div>
    <?php } ?>
</div>