<?php if(empty($hide_form_search)): ?>
    <div class="g-form-control">
        <ul class="nav nav-tabs" role="tablist">
            <?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if (empty($allServices[$service_type])) {
                            continue;
                        }
                        $module = new ($allServices[$service_type])();
                    ?>
                    <li role="bc_<?php echo e($service_type); ?>">
                        <a href="#bc_<?php echo e($service_type); ?>" class="<?php if($number == 0): ?> active <?php endif; ?>"
                            aria-controls="bc_<?php echo e($service_type); ?>" role="tab" data-toggle="tab">
                            <i class="<?php echo e($module->getServiceIconFeatured()); ?>"></i>
                            <?php echo e(!empty($modelBlock['title_for_' . $service_type]) ? $modelBlock['title_for_' . $service_type] : $module->getModelName()); ?>

                        </a>
                    </li>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </ul>
        <div class="tab-content">
            <?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if (empty($allServices[$service_type])) {
                            continue;
                        }
                        $module = new ($allServices[$service_type])();
                    ?>
                    <div role="tabpanel" class="tab-pane <?php if($number == 0): ?> active <?php endif; ?>"
                        id="bc_<?php echo e($service_type); ?>">
                        <?php if ($__env->exists(ucfirst($service_type) . '::frontend.layouts.search.form-search')) echo $__env->make(ucfirst($service_type) . '::frontend.layouts.search.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    
    <div style="display: none;"></div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Template/Views/frontend/blocks/form-search-all-service/form-search.blade.php ENDPATH**/ ?>