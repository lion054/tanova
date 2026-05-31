<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Category')); ?></strong></div>
    <div class="panel-body">
        <select name="cat_id" class="form-control">
            <option value=""><?php echo e(__("-- Please Select --")); ?></option>
            <?php
            $traverse = function ($categories, $prefix = '') use (&$traverse, $row) {
                foreach ($categories as $category) {
                    $selected = '';
                    if ($row->cat_id == $category->id)
                        $selected = 'selected';
                    printf("<option value='%s' %s>%s</option>", $category->id, $selected, $prefix . ' ' . $category->name);
                    $traverse($category->children, $prefix . '-');
                }
            };
            $traverse($category_tree);
            ?>
        </select>
    </div>
</div>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Skill Level')); ?></strong></div>
    <div class="panel-body">
        <select name="level_id" class="form-control">
            <option value=""><?php echo e(__("-- Please Select --")); ?></option>
            <?php $__currentLoopData = $levels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($level->id); ?>" <?php if($row->level_id == $level->id): ?> selected <?php endif; ?>><?php echo e($level->translate()->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
</div>
<?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="panel">
        <div class="panel-title"><strong><?php echo e(__('Attribute: :name',['name'=>$attribute->name])); ?></strong></div>
        <div class="panel-body">
            <div class="terms-scrollable">
                <?php $__currentLoopData = $attribute->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="term-item">
                        <input <?php if(!empty($selected_terms) and $selected_terms->contains($term->id)): ?> checked <?php endif; ?> type="checkbox" name="terms[]" value="<?php echo e($term->id); ?>">
                        <span class="term-name"><?php echo e($term->name); ?></span>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Course/Views/admin/course/attributes.blade.php ENDPATH**/ ?>