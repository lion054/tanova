<section data-anim-wrap class="masthead -type-3 relative z-5 form_search_style_2">
    <div data-anim-child="fade delay-1" class="masthead__bg bg-dark-3">
        <img src="<?php echo e($bg_image_url); ?>"alt="image" data-src="<?php echo e($bg_image_url); ?>" class="js-lazy">
    </div>
    <div class="container">
        <div class="row justify-center">
            <div class="col-xl-10">
                <div class="text-center">
                    <h1 data-anim-child="slide-up delay-4" class="text-60 lg:text-40 md:text-30 text-white"><?php echo e($title); ?></h1>
                    <p data-anim-child="slide-up delay-5" class="text-white mt-6 md:mt-10"><?php echo e($sub_title); ?></p>
                </div>
                <div data-anim-child="slide-up delay-6" class="masthead__tabs">
                    <div class="tabs -bookmark js-tabs">
                        <div class="tabs__controls d-flex items-center js-tabs-controls">
                            <?php if($service_types): ?>
                                <?php $allServices = get_bookable_services(); $number = 0; ?>
                                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        if(empty($allServices[$service_type])) continue;
                                        $service = $allServices[$service_type];
                                    ?>
                                    <div class="">
                                        <button class="tabs__button px-30 py-20 rounded-4 fw-600 text-white js-tabs-button <?php if($number==0): ?> is-tab-el-active <?php endif; ?>" data-tab-target=".-tab-item-<?php echo e($service_type); ?>">
                                            <i class="<?php echo e($icons[$service_type]); ?> text-20 mr-10"></i>
                                            <?php echo e($service::getModelName()); ?>

                                        </button>
                                    </div>
                                    <?php $number++; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                        <div class="tabs__content js-tabs-content">
                            <?php if($service_types): ?>
                                <?php $number = 0; ?>
                                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        if(empty($allServices[$service_type])) continue;
                                    ?>
                                    <div class="tabs__pane -tab-item-<?php echo e($service_type); ?> <?php if($number==0): ?> is-tab-el-active <?php endif; ?>">
                                        <?php switch($service_type):
                                            case ('visa'): ?>
                                                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split($service_type . '::search-form', ['shouldRedirect' => true, 'style' => 'normal2']);

$__html = app('livewire')->mount($__name, $__params, 'lw-3293556605-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                                            <?php break; ?>
                                            <?php default: ?>
                                                <?php echo $__env->make(ucfirst($service_type).'::frontend.layouts.search.form-search', ['style' => 'normal2'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <?php endswitch; ?>
                                    </div>
                                    <?php $number++; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Template/Views/frontend/blocks/form-search-all-service/style-normal-2.blade.php ENDPATH**/ ?>