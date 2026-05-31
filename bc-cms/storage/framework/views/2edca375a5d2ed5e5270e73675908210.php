<section class="layout-pt-md layout-pb-lg bc-gotrip-list-hotel layout_<?php echo e($style_list); ?>">
    <div data-anim-wrap class="container">
        <div data-anim-child="slide-up delay-1" class="row justify-center text-center">
            <div class="col-auto">
                <div class="sectionTitle -md">
                    <h2 class="sectionTitle__title"><?php echo e($title ?? ''); ?></h2>
                    <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($desc ?? ''); ?></p>
                </div>
            </div>
        </div>
        <?php if(!empty($locations)): ?>
        <div data-anim-child="slide-up delay-2" class="tabs -pills-2 pt-40 js-tabs">
            <div class="tabs__controls row x-gap-15 justify-center js-tabs-controls">
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $translation = $location->translate(); ?>
                    <div class="col-auto">
                        <button class="tabs__button mb-5 text-14 fw-500 px-20 py-10 rounded-4 bg-light-2 js-tabs-button <?php if($k==0): ?> is-tab-el-active <?php endif; ?>" data-tab-target=".-tab-<?php echo e($location->slug); ?>"><?php echo e($translation->name); ?></button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="tabs__content pt-40 js-tabs-content">
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="tabs__pane -tab-<?php echo e($location->slug); ?> <?php if($k==0): ?> is-tab-el-active <?php endif; ?>">
                        <div class="row y-gap-30">
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($row->location_id == $location->id): ?>
                                    <div class="col-xl-3 col-lg-3 col-sm-6">
                                        <?php echo $__env->make('Hotel::frontend.layouts.search.loop-grid', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="row justify-center pt-40">
                    <div class="col-auto">
                        <a href="<?php echo e(route('hotel.search')); ?>" class="button px-40 h-50 -outline-blue-1 text-blue-1">
                            <?php echo e(__('View All')); ?> <div class="icon-arrow-top-right ml-15"></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/blocks/list-hotel/normal.blade.php ENDPATH**/ ?>