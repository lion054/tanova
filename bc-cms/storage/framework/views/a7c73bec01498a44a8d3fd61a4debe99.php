<?php if(!empty($field['attr']) and !empty($attr = \Modules\Core\Models\Attributes::find($field['attr']))): ?>
    <?php
        $selected = (array) Request::query('terms');
    ?>
    <?php if($attr): ?>
        <h5 class="text-18 fw-500 mb-10"><?php echo e($field['title'] ?? ""); ?></h5>

        <div class="sidebar-checkbox">
            <?php $__currentLoopData = $attr->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $translate = $term->translate();
                ?>
                <div class="row y-gap-10 items-center justify-between">
                    <div class="col-auto">
                        <div class="d-flex items-center">
                            <div class="form-checkbox ">
                                <input type="checkbox" name="terms[]" id="term_<?php echo e($term->id); ?>" value="<?php echo e($term->id); ?>" <?php if(!empty($selected) && in_array($term->id, $selected)): ?> checked <?php endif; ?>>
                                <div class="form-checkbox__mark">
                                    <div class="form-checkbox__icon icon-check"></div>
                                </div>
                            </div>

                            <label class="text-15 ml-10" for="term_<?php echo e($term->id); ?>"><?php echo e($translate->name); ?></label>

                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Boat/Views/frontend/layouts/search/fields/attr.blade.php ENDPATH**/ ?>