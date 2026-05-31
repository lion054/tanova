<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Property Content")); ?></strong></div>
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

        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__("Youtube Video")); ?></label>
                <input type="text" name="video" class="form-control" value="<?php echo e($row->video); ?>" placeholder="<?php echo e(__("Youtube link video")); ?>">
            </div>
        <?php endif; ?>
        <?php if(is_default_lang()): ?>
            <div class="form-group">
                <label class="control-label"><?php echo e(__("Video Background")); ?></label>
                <div class="form-group-image">
                    <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('banner_image_id',$row->banner_image_id); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(is_default_lang()): ?>
            <div class="panel">
                <div class="panel-title"><strong><?php echo e(__("Gallery")); ?></strong></div>
                <div class="panel-body">
                    <?php echo \Modules\Media\Helpers\FileHelper::fieldGalleryUpload('gallery',$row->gallery); ?>

                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo $__env->make('Property::admin.property.pricing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php if(is_default_lang()): ?>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Extra Info")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("No. Bed")); ?></label>
                    <input type="number" value="<?php echo e($row->bed); ?>" placeholder="<?php echo e(__("Example: 3")); ?>" name="bed" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("No. Bathroom")); ?></label>
                    <input type="number" value="<?php echo e($row->bathroom); ?>" placeholder="<?php echo e(__("Example: 5")); ?>" name="bathroom" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Square")); ?></label>
                    <input type="number" value="<?php echo e($row->square); ?>" placeholder="<?php echo e(__("Example: 100")); ?>" name="square" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Garages")); ?></label>
                    <input type="number" value="<?php echo e($row->garages); ?>" placeholder="<?php echo e(__("Example: 100")); ?>" name="garages" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Year built")); ?></label>
                    <input type="number" value="<?php echo e($row->year_built); ?>" placeholder="<?php echo e(__("Example: 2020")); ?>" name="year_built" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Area")); ?></label>
                    <input type="number" value="<?php echo e($row->area); ?>" placeholder="<?php echo e(__("Example: 100")); ?>" name="area" class="form-control" min="0">
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if(is_default_lang()): ?>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Additional details")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Deposit")); ?></label>
                    <input type="text" value="<?php echo e($row->deposit); ?>" name="deposit" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Pool size")); ?></label>
                    <input type="text" value="<?php echo e($row->pool_size); ?>" name="pool_size" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Additional zoom")); ?></label>
                    <input type="text" value="<?php echo e($row->additional_zoom); ?>"  name="additional_zoom" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Remodal year")); ?></label>
                    <input type="number" value="<?php echo e($row->remodal_year); ?>" name="remodal_year" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Amenities")); ?></label>
                    <input type="text" value="<?php echo e($row->amenities); ?>" name="amenities" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo e(__("Equipment")); ?></label>
                    <input type="text" value="<?php echo e($row->equipment); ?>" name="equipment" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/admin/property/content.blade.php ENDPATH**/ ?>