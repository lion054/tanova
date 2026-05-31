<div>
    <?php if(!empty($list_item)): ?>
        <div class="form-search-all-service home4-form-search-hotel">
            <div class="tabs__content">
                <section data-anim-wrap class="masthead -type-4 <?php echo e($style ?? ''); ?>">
                    <div data-anim-child="slide-up" class="masthead-slider overflow-x-hidden js-masthead-slider-4">
                        <div class="swiper-wrapper">
                            <?php $__currentLoopData = $list_item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="swiper-slide">
                                    <div class="masthead__image">
                                        <img src="<?php echo e(get_file_url($item['bg_image'], 'full')); ?>" alt="<?php echo e($item['title']); ?>">
                                    </div>

                                    <div class="container">
                                        <div class="row justify-center">
                                            <div class="col-xl-9">
                                                <div class="masthead__content">
                                                    <div class="text-center content-wrap">
                                                        <h1 data-anim-child="slide-up delay-2" class="text-60 lg:text-40 md:text-30 text-white">
                                                            <?php echo e($item['title']); ?></h1>
                                                        <p data-anim-child="slide-up delay-3" class="text-white mt-5"><?php echo e($item['sub_title']); ?></p>

                                                        <?php if($item['discover_text']): ?>
                                                            <div data-anim-child="slide-up delay-4" class="d-inline-block">
                                                                <a href="<?php echo e($item['discover_link']); ?>"
                                                                    class="button -md -outline-white h-50 text-white mt-30"><?php echo e($item['discover_text']); ?></a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="masthead-slider__nav -prev js-prev">
                            <button class="button -outline-white size-50 flex-center text-white rounded-full">
                                <i class="icon-arrow-left"></i>
                            </button>
                        </div>

                        <div class="masthead-slider__nav -next js-next">
                            <button class="button -outline-white size-50 flex-center text-white rounded-full">
                                <i class="icon-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="masthead__scroll">
                        <div class="text-14 text-white"><?php echo e(__('Scroll Now')); ?></div>
                        <div class="-line"></div>
                    </div>
                </section>
                <?php echo $__env->make('Hotel::frontend.layouts.search.form-search', ['style' => 'hotel_carousel'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Hotel/Views/frontend/blocks/form-search-hotel/index.blade.php ENDPATH**/ ?>