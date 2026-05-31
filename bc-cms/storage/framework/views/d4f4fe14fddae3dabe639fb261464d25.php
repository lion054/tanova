<section class="section-bg layout-pt-lg layout-pb-lg">
    <?php if(!empty($bg_image_url)): ?>
        <div class="section-bg__item col-12">
            <img src="<?php echo e($bg_image_url); ?>" alt="<?php echo e($title); ?>">
        </div>
    <?php endif; ?>
    <div class="container">
        <div class="row justify-center text-center">
            <div class="col-xl-6 col-lg-8 col-md-10">
                <h1 class="text-40 md:text-25 fw-600 text-white">
                    <?php echo $title; ?>

                </h1>
                <div class="text-white mt-15"> <?php echo e($sub_title); ?></div>
                <?php if($link_title): ?>
                    <div class="d-inline-block">
                        <a href="<?php echo e($link_more); ?>" class="button -md -blue-1 w-180 bg-white text-dark-1 mt-30 md:mt-20"><?php echo e($link_title); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/blocks/call-to-action/style-2.blade.php ENDPATH**/ ?>