<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Course Content")); ?></strong></div>
    <div class="panel-body">
        <div class="form-group magic-field" data-id="title" data-type="title">
            <label class="control-label"><?php echo e(__("Title")); ?></label>
            <input type="text" value="<?php echo e($translation->title); ?>" placeholder="<?php echo e(__("Title")); ?>" name="title" class="form-control">
        </div>
        <div class="form-group magic-field" data-id="content" data-type="content">
            <label class="control-label"><?php echo e(__("Content")); ?></label>
            <div class="">
                <textarea name="content" class="d-none has-ckeditor" id="content" cols="30" rows="10"><?php echo e($translation->content); ?></textarea>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label"><?php echo e(__("Short Description")); ?></label>
            <div class="">
                <textarea name="short_desc" class="form-control" cols="30" rows="3"><?php echo e($translation->short_desc); ?></textarea>
            </div>
        </div>

        <div class="row">
            <?php if(is_default_lang()): ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo e(__("Duration")); ?></label>
                        <div class="input-group">
                            <input type="text" value="<?php echo e($row->duration); ?>" placeholder="<?php echo e(__("Ex: 100")); ?>" name="duration" class="form-control">
                            <div class="input-group-append">
                                <span class="input-group-text small"><?php echo e(__("Minutes")); ?></span>
                            </div>
                        </div>
                        <span><i class="small"><?php echo e(__("If left blank, the total time of the lectures will automatically be calculated")); ?></i></span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label"><?php echo e(__("Language")); ?></label>
                    <input type="text" name="language" class="form-control" value="<?php echo e($row->language); ?>" placeholder="<?php echo e(__("Language")); ?>">
                </div>
            </div>
        </div>

        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__("Preview Video Url")); ?></label>
                <input type="text" name="preview_url" class="form-control" value="<?php echo e($row->preview_url); ?>" placeholder="<?php echo e(__("Video Url")); ?>">
            </div>
        <?php endif; ?>

    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/course/content.blade.php ENDPATH**/ ?>