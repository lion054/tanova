<div class="form-group">
    <label><?php echo e(__("Name")); ?></label>
    <input type="text" value="<?php echo e($translation->name); ?>" placeholder="<?php echo e(__("Category name")); ?>" name="name" class="form-control">
</div>
<?php if(is_default_lang()): ?>
    <div class="form-group">
        <label><?php echo e(__("Parent")); ?></label>
        <select name="parent_id" class="form-control">
            <option value=""><?php echo e(__("-- Please Select --")); ?></option>
            <?php
            $traverse = function ($categories, $prefix = '') use (&$traverse, $row) {
                foreach ($categories as $category) {
                    if ($category->id == $row->id) {
                        continue;
                    }
                    $selected = '';
                    if ($row->parent_id == $category->id)
                        $selected = 'selected';
                    printf("<option value='%s' %s>%s</option>", $category->id, $selected, $prefix . ' ' . $category->name);
                    $traverse($category->children, $prefix . '-');
                }
            };
            $traverse($parents);
            ?>
        </select>
    </div>
    <div class="form-group">
        <label class="control-label"><?php echo e(__("Image")); ?></label>
        <div class="">        
            <?php echo \Modules\Media\Helpers\FileHelper::fieldUpload('image_id',$row->image_id); ?>

        </div>
    </div>
    
    <div class="form-group">
        <label><?php echo e(__("Status")); ?></label>
        <select name="status" class="form-control">
            <option value="publish"><?php echo e(__("Publish")); ?></option>
            <option value="draft" <?php if($row->status=='draft'): ?> selected <?php endif; ?>><?php echo e(__("Draft")); ?></option>
        </select>
    </div>

<?php endif; ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/category/form.blade.php ENDPATH**/ ?>