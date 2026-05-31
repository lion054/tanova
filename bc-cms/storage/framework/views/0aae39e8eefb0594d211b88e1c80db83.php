<section data-anim-wrap class="flight-search masthead -type-10">
    <div class="container-1500">
        <div class="row">
            <div class="col-lg-auto">
                <div class="masthead__content">
                    <h1 data-anim-child="slide-up delay-1" class="text-60 lg:text-40 sm:text-30"><?php echo e($title); ?></h1>
                    <p data-anim-child="slide-up delay-2" class="mt-5"><?php echo e($sub_title); ?></p>

                    <div data-anim-child="slide-up delay-3">

                        <div class="mainSearch -col-4 -w-1070 bg-white shadow-1 rounded-4 pr-20 py-20 lg:px-20 lg:pt-5 lg:pb-20 mt-15">
                                <div class="g-form-control">
                                    <?php echo $__env->make('Flight::frontend.layouts.search.form-search',['style'=>'flightCarousel'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php if(!empty($list_slider)): ?>
        <div data-anim-child="slide-left delay-6" class="masthead__image">
            <div class="row y-gap-30">
                <?php $__currentLoopData = $list_slider; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $img = get_file_url($item['bg_image'],'full') ?>
                    <div class="col-auto">
                        <img src="<?php echo e($img); ?>" alt="image" class="rounded-16">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
<?php endif; ?>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/blocks/form-search-flight/index.blade.php ENDPATH**/ ?>