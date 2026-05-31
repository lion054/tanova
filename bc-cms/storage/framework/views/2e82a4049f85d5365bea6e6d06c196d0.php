<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Social Info")); ?></strong></div>
    <div class="panel-body">
        <div class="form-group">
            <div class="form-controls">
                <div class="form-group-item">
                    <div class="form-group-item">
                        <div class="g-items-header">
                            <div class="row">
                                <div class="col-md-3"><?php echo e(__("Name social")); ?></div>
                                <div class="col-md-4"><?php echo e(__('Code icon')); ?></div>
                                <div class="col-md-4"><?php echo e(__('Link social')); ?></div>
                                <div class="col-md-1"></div>
                            </div>
                        </div>
                        <div class="g-items">
                            <?php
                            $social = $row->social;

                            if(!empty($social)) $social = json_decode($social,true);
                            if(empty($social) or !is_array($social))
                                $social = [];
                            ?>
                            <?php $__currentLoopData = $social; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="item" data-number="<?php echo e($key); ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <input type="text" name="social[<?php echo e($key); ?>][title]" class="form-control" value="<?php echo e($item['title'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="social[<?php echo e($key); ?>][code]" class="form-control" value="<?php echo e($item['code']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" name="social[<?php echo e($key); ?>][link]" class="form-control" value="<?php echo e($item['link']); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <div class="text-right">
                            <span class="btn btn-info btn-sm btn-add-item"><i class="icon ion-ios-add-circle-outline"></i> <?php echo e(__('Add item')); ?></span>
                        </div>
                        <div class="g-more hide">
                            <div class="item" data-number="__number__">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" __name__="social[__number__][title]" class="form-control" value="">
                                    </div>

                                    <div class="col-md-4">
                                        <input type="text" __name__="social[__number__][code]" class="form-control" value="">
                                    </div>

                                    <div class="col-md-4">
                                        <input type="text" __name__="social[__number__][link]" class="form-control" value="">
                                    </div>
                                    <div class="col-md-1">
                                        <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Agency/Views/admin/agency/include/social.blade.php ENDPATH**/ ?>