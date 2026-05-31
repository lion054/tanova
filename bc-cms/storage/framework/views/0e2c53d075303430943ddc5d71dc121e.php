<section class="layout-pt-lg layout-pb-md">
    <div data-anim-wrap class="container">
        <div data-anim-child="slide-up delay-1" class="row y-gap-20 justify-center text-center">
            <div class="col-auto">
                <div class="sectionTitle -md">
                    <h2 class="sectionTitle__title"><?php echo e($title ?? ''); ?></h2>
                    <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($desc ?? ''); ?></p>
                </div>
            </div>
        </div>
        <div data-anim-child="slide-up delay-2" class="relative pt-40 js-section-slider" data-gap="30" data-slider-cols="xl-6 lg-4 md-3 sm-2 base-1">
            <div class="swiper-wrapper">
                <?php if($rows): ?>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $translation = $row->translate(); ?>
                        <div class="swiper-slide">
                            <?php if($to_location_detail): ?> <a href="<?php echo e($row->getDetailUrl()); ?>"> <?php endif; ?>
                                <div class="citiesCard -type-2">
                                    <?php if($row->image_id): ?>
                                        <div class="citiesCard__image rounded-4 ratio ratio-1:1">
                                            <img class="img-ratio rounded-4 js-lazy" data-src="<?php echo e($row->getImageUrl()); ?>" src="<?php echo e($row->getImageUrl()); ?>" alt="<?php echo e($translation->name); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="citiesCard__content mt-10">
                                        <h4 class="text-18 lh-13 fw-500 text-dark-1"><?php echo e($translation->name); ?></h4>
                                        <div class="text-14 text-light-1">
                                            <?php if(is_array($service_type)): ?>
                                                <?php $count_all = 0; ?>
                                                <?php $__currentLoopData = $service_type; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php $count_all += (int) $row->getDisplayNumberServiceInLocation($type) ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php if(!empty($count_all)): ?>
                                                    <span><?php echo e($count_all); ?> <?php echo e(__("travellers")); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if(!empty($text_service = $row->getDisplayNumberServiceInLocation($service_type))): ?>
                                                    <span><?php echo e($text_service); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php if($to_location_detail): ?> </a> <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </div>
            <button class="section-slider-nav -prev flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-prev">
                <i class="icon icon-chevron-left text-12"></i>
            </button>
            <button class="section-slider-nav -next flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-next">
                <i class="icon icon-chevron-right text-12"></i>
            </button>
        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Location/Views/frontend/blocks/list-locations/style_3.blade.php ENDPATH**/ ?>