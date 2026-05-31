<?php if(!empty($field['attr']) and !empty($attr = \Modules\Core\Models\Attributes::find($field['attr']))): ?>
    <?php
        $attr_translate = $attr->translate();
        if(request()->query('term_id'))
            $selected = \Modules\Core\Models\Terms::find(request()->query('term_id'));
        else $selected = false;
        $list_cat_json = [];
    ?>
    <?php if($attr): ?>
        <div class="filter-item">
            <div class="form-group">
                <i class="field-icon icofont-beach"></i>
                <div class="form-content">
                    <?php $__currentLoopData = $attr->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $translate = $term->translate();
                        $list_cat_json[] = [
                            'id' => $term->id,
                            'title' => $translate->name,
                        ];
                        ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <div class="smart-search">
                        <input type="text" class="smart-select parent_text form-control" readonly placeholder="<?php echo e(__("All :name",['name'=>$attr_translate->name])); ?>" value="<?php echo e($selected ? $selected->name ?? '' :''); ?>" data-default="<?php echo e(json_encode($list_cat_json)); ?>">
                        <input type="hidden" class="child_id" name="terms[]" value="<?php echo e(Request::query('term_id')); ?>">
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Car/Views/frontend/layouts/search-map/fields/attr.blade.php ENDPATH**/ ?>