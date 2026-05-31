<?php if(!empty($field['attr']) and !empty($attr = \Modules\Core\Models\Attributes::find($field['attr']))): ?>
    <?php
        $attr_translate = $attr->translate();
        if(request()->query('term_id'))
            $selected = \Modules\Core\Models\Terms::find(request()->query('term_id'));
        else $selected = false;
        $list_cat_json = [];
    ?>
    <?php if($attr): ?>
        <div class="searchMenu-loc js-form-dd js-liverSearch item">
            <div data-x-dd-click="searchMenu-loc">
                <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e($field['title'] ?? ""); ?></h4>
                <div class="text-15 text-light-1 ls-2 lh-16">
                    <input type="hidden" name="terms[]" class="js-search-get-id" value="<?php echo e(Request::query('term_id')); ?>">
                    <input autocomplete="off" type="search" placeholder="<?php echo e(__("All :name",['name'=>$attr_translate->name])); ?>" class="js-search js-dd-focus" value="<?php echo e($selected ? $selected->name ?? '' :''); ?>" />
                </div>
            </div>
            <div class="searchMenu-loc__field shadow-2 js-popup-window" data-x-dd="searchMenu-loc" data-x-dd-toggle="-is-active">
                <div class="bg-white px-30 py-30 sm:px-0 sm:py-15 rounded-4">
                    <div class="y-gap-5 js-results">
                        <?php $__currentLoopData = $attr->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $translate = $term->translate();
                                $list_cat_json[] = [
                                    'id' => $term->id,
                                    'title' => $translate->name,
                                ];
                            ?>
                            <div class="-link d-block col-12 text-left rounded-4 px-20 py-15 js-search-option" data-id="<?php echo e($term->id); ?>">
                                <div class="d-flex align-items-center">
                                    <div class="icon-location-2 text-light-1 text-20 pt-4"></div>
                                    <div class="ml-10">
                                        <div class="text-15 lh-12 fw-500 js-search-option-target"><?php echo e($translate->name); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/common/search/fields/attr.blade.php ENDPATH**/ ?>