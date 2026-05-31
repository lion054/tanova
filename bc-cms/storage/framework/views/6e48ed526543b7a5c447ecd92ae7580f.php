<div>
    <div class="effect background-video-container">
        <?php if(!empty($video_url)): ?>
            <?php $video_id = handleVideoUrl($video_url,true) ?>
            <iframe class="background-video-embed" frameborder="0"
                src="https://www.youtube.com/embed/<?php echo e($video_id); ?>?controls=0&autoplay=1&mute=1&playsinline=1&playlist=<?php echo e($video_id); ?>&loop=1"></iframe>
        <?php endif; ?>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="text-heading"><?php echo e($title); ?></h1>
                <div class="sub-heading"><?php echo e($sub_title); ?></div>
                <?php echo $__env->make('Template::frontend.blocks.form-search-all-service.form-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Template/Views/frontend/blocks/form-search-all-service/style-video.blade.php ENDPATH**/ ?>