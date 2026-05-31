<div class="topic-sidebars list-cats">
    <?php if(!empty($is_agent)): ?>
        <div class="widget mb-5">
            <div class="ticket-card-action">
                <div class="card-header text-center">
                    <?php echo e(__('Ticket Status')); ?>

                </div>
                <form action="<?php echo e(route('support.ticket.action',['id'=>$row->id])); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <div class="card-body">
                        <label class="d-block">
                            <input
                                type="radio" value="open" id="status_new" name="status" <?php if($row->status == 'open'): ?> checked <?php endif; ?>>
                            <?php echo e(__("Open")); ?>

                        </label>
                        <label class="d-block">
                            <input
                                type="radio" value="closed" id="status_closed" name="status" <?php if($row->status == 'closed'): ?> checked <?php endif; ?>>
                            <?php echo e(__("Closed")); ?>

                        </label>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-block btn-primary" name="action" value="status"><?php echo e(__("Save Status")); ?></button>
                    </div>
                </form>
            </div>
            <div class="ticket-card-action mt-4 mb-5">
                <div class="card-header  text-center">
                    <?php echo e(__('User Notes')); ?>

                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('support.ticket.action',['id'=>$row->id])); ?>" method="post">
                        <?php echo csrf_field(); ?>
                        <label for=""><?php echo e(__("Add note")); ?></label>
                        <textarea name="note_content" class="form-control" cols="30" rows="3"></textarea>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" name="action" value="user_note"><?php echo e(__("Add user note")); ?></button>
                        </div>
                    </form>
                </div>
                <ul class="list-group list-group-flush">
                    <?php $__currentLoopData = $row->customer->notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="list-group-item">
                            <div class="note-content">
                                <?php echo nl2br($note->content); ?>

                            </div>
                            <small>
                                <i><?php echo e(display_datetime($note->created_at)); ?></i>
                            </small>
                            <div class="d-flex justify-content-between mt-2">
                                <a data-toggle="modal" data-target="#edit_note_<?php echo e($note->id); ?>" href="#" class="doc_border_btn btn_small">Edit</a>
                                <form
                                    onsubmit="return confirm('Do you want to delete this note')"
                                    action="<?php echo e(route('support.note.delete',['id'=>$note->id])); ?>"
                                    method="post"
                                > <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-link btn-sm text-danger"><?php echo e(__('Delete')); ?></button>
                                </form>
                            </div>
                            <form action="<?php echo e(route('support.note.update',['id'=>$note->id])); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                <div class="modal" tabindex="-1" id="edit_note_<?php echo e($note->id); ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Note</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <h5><?php echo e(__("Old")); ?></h5>
                                                <p><?php echo nl2br($note->content); ?></p>
                                                <h5><?php echo e(__("New")); ?></h5>
                                                <textarea rows="5" class="form-control" name="content"><?php echo e($note->content); ?></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>

    <?php endif; ?></div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Support/Views/frontend/layouts/ticket/detail-sidebar.blade.php ENDPATH**/ ?>