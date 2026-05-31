
<?php $__env->startSection('content'); ?>
    <div class="container">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Import Template')); ?></h1>
            <div class="title-actions">
                <a href="<?php echo e(route('template.admin.index')); ?>" class="btn btn-primary"><?php echo e(__('All Templates')); ?></a>
            </div>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                    <form method="post" action="<?php echo e(route('template.admin.importTemplate')); ?>" class="" enctype="multipart/form-data">
                        <?php echo e(csrf_field()); ?>

                        <div class="custom-file mb-3">
                            <input type="file" name="file" class="custom-file-input" id="customFile" accept="application/json" required>
                            <label class="custom-file-label" for="customFile"><?php echo e(__("Choose file")); ?></label>
                        </div>
                        <button  class="btn-info btn btn-icon dungdt-apply-form-btn" type="submit"><?php echo e(__('Import')); ?></button>
                    </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('js'); ?>
    <script>
        $('#customFile').on('change',function(){
            //get the file name
            var fileName = $(this).val();
            //replace the "Choose a file" label
            $(this).next('.custom-file-label').html(fileName);
        })
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Template/Views/admin/import.blade.php ENDPATH**/ ?>