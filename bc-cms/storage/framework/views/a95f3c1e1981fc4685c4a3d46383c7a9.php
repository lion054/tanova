<link rel="stylesheet" href="<?php echo e(asset('libs/bootstrap4.0/css/bootstrap.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('module/media/css/browser.css?_ver='.config('app.asset_version'))); ?>">
<style>
    #cdn-browser-modal{
        opacity: 1;
        display: block;
    }
    #cdn-browser-modal .modal-dialog{
        width: 100%;
        margin: 0px;
        padding: 0px;
        max-width: none;
        transform: none;
    }
    #cdn-browser-modal .modal-content{
        padding: 0px;
        border:0px;
        margin: 0px;
    }
</style>
<!-- CSRF Token -->
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<?php echo $__env->make('Media::browser', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
    var bookingCore  = {
        url:'<?php echo e(url('/')); ?>',
        map_provider:'<?php echo e(setting_item('map_provider')); ?>',
        map_gmap_key:'<?php echo e(setting_item('map_gmap_key')); ?>'
    };

</script>
<script src="<?php echo e(asset('libs/jquery-3.6.3.min.js')); ?>"></script>
<script src="<?php echo e(asset('libs/vue/vue'.(!env('APP_DEBUG') ? '.min':'').'.js')); ?>"></script>
<script src="<?php echo e(asset('libs/bootstrap4.0/js/bootstrap.min.js')); ?>"></script>
<script src="<?php echo e(asset('module/media/js/browser.js?_ver='.config('app.asset_version'))); ?>"></script>
<script>
    (function ($) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#cdn-browser-modal').modal('show');
    })(jQuery)
</script>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Media/Views/ckeditor.blade.php ENDPATH**/ ?>