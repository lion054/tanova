<?php if(!empty($list_item)): ?>
<section class="section-bg layout-pt-lg layout-pb-lg">
    <div class="section-bg__item -mx-20 bg-light-2"></div>

    <div data-anim-wrap class="container">
        <div data-anim-child="slide-up delay-1" class="row justify-center text-center">
            <div class="col-auto">
                <div class="sectionTitle -md">
                    <h2 class="sectionTitle__title"><?php echo e($title ?? ''); ?></h2>
                    <p class=" sectionTitle__text mt-5 sm:mt-0"><?php echo e($subtitle ?? ''); ?></p>
                </div>
            </div>
        </div>

        <div class="relative mt-80 md:mt-40 js-section-slider" data-gap="30" data-slider-cols="xl-3 lg-3 md-2 sm-1 base-1">
            <div class="swiper-wrapper">
                <?php $i = 2 ?>
                <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div data-anim-child="slide-up delay-<?php echo e($i); ?>" class="swiper-slide">
                        <div class="testimonials -type-1 bg-white rounded-4 pt-40 pb-30 px-40">
                            <h4 class="text-16 fw-500 text-blue-1 mb-20"><?php echo e($item['title'] ?? ''); ?></h4>
                            <p class="testimonials__text lh-18 fw-500 text-dark-1"><?php echo e($item['desc'] ?? ''); ?></p>

                            <div class="pt-20 mt-28 border-top-light">
                                <div class="row x-gap-20 y-gap-20 items-center">
                                    <div class="col-auto">
                                        <img class="size-60" src="<?php echo e(get_file_url($item['avatar'])); ?>" alt="<?php echo e($item['name']); ?>">
                                    </div>

                                    <div class="col-auto">
                                        <div class="text-15 fw-500 lh-14"><?php echo e($item['name']); ?></div>
                                        <div class="text-14 lh-14 text-light-1 mt-5"><?php echo e($item['job']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $i++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>


            <button class="section-slider-nav -prev flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-prev">
                <i class="icon icon-chevron-left text-12"></i>
            </button>

            <button class="section-slider-nav -next flex-center button -blue-1 bg-white shadow-1 size-40 rounded-full sm:d-none js-next">
                <i class="icon icon-chevron-right text-12"></i>
            </button>

        </div>

        <div data-anim-child="fade delay-6" class="row y-gap-30 items-center pt-40 sm:pt-20">
            <div class="col-xl-4">
                <div class="row y-gap-30 text-dark-1">
                    <div class="col-sm-5 col-6">
                        <div class="text-30 lh-15 fw-600"><?php echo e($happy_people_number ?? ''); ?></div>
                        <div class="lh-15"><?php echo e($happy_people_text ?? ''); ?></div>
                    </div>

                    <div class="col-sm-5 col-6">
                        <div class="text-30 lh-15 fw-600"><?php echo e($overall_rating_number ?? ''); ?></div>
                        <div class="lh-15"><?php echo e($overall_rating_text ?? ''); ?></div>
                        <?php if(!empty($overall_rating_star)): ?>
                            <div class="d-flex x-gap-5 items-center pt-10">
                                <?php for($i = 1; $i <= $overall_rating_star; $i++): ?>
                                    <div class="icon-star text-dark-1 text-10"></div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($list_trusted)): ?>
                <div class="col-xl-8">
                    <div class="row y-gap-30 justify-between items-center">
                        <?php $__currentLoopData = $list_trusted; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $avatar_url = get_file_url($item['avatar'], 'full') ?>
                            <div class="col-md-auto col-sm-6">
                                <div class="d-flex justify-center">
                                    <img src="<?php echo e($avatar_url); ?>" alt="image">
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/testimonial/style_3.blade.php ENDPATH**/ ?>