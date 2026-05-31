<div class="item d-flex align-items-center">
    <?php
        $param = request()->input();
        $orderby =  request()->input("orderby");
    ?>
    <div class="mr-5">
        <?php echo e(__("Sort by:")); ?>

    </div>
    <div class="dropdown">
        <span class="button -blue-1 h-40 px-20 rounded-100 bg-blue-1-05 text-15 text-blue-1"  data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="icon-up-down text-14 mr-10"></i>
            <?php switch($orderby):
                case ("price_low_high"): ?>
                    <?php echo e(__("Price (Low to high)")); ?>

                    <?php break; ?>
                <?php case ("price_high_low"): ?>
                    <?php echo e(__("Price (High to low)")); ?>

                    <?php break; ?>
                <?php case ("rate_high_low"): ?>
                    <?php echo e(__("Rating (High to low)")); ?>

                    <?php break; ?>
                <?php default: ?>
                    <?php echo e(__("Recommended")); ?>

            <?php endswitch; ?>
        </span>
        <div class="dropdown-menu dropdown-menu-right bc-order" aria-labelledby="dropdownMenuButton">
            <?php $param['orderby'] = "" ?>
            <a class="dropdown-item" data-order="<?php echo e($param['orderby']); ?>" href="javascript:void(0)"><?php echo e(__("Recommended")); ?></a>
            <?php $param['orderby'] = "price_low_high" ?>
            <a class="dropdown-item" data-order="<?php echo e($param['orderby']); ?>" href="javascript:void(0)"><?php echo e(__("Price (Low to high)")); ?></a>
            <?php $param['orderby'] = "price_high_low" ?>
            <a class="dropdown-item" data-order="<?php echo e($param['orderby']); ?>" href="javascript:void(0)"><?php echo e(__("Price (High to low)")); ?></a>
            <?php $param['orderby'] = "rate_high_low" ?>
            <a class="dropdown-item" data-order="<?php echo e($param['orderby']); ?>" href="javascript:void(0)"><?php echo e(__("Rating (High to low)")); ?></a>
            <input type="hidden" class="orderby" name="orderby" value="">
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Tour/Views/frontend/layouts/search-map/orderby.blade.php ENDPATH**/ ?>