
<div class="item d-flex align-items-center">
    <?php
        $param = request()->input();
        $orderby =  request()->input("orderby");
    ?>
    <div class="item-title mr-10">
        <?php echo e(__("Sort by:")); ?>

    </div>
    <div class="button -blue-1 h-40 px-30 rounded-100 bg-blue-1-05 text-15 text-blue-1 dropdown">
                            <span class=" dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php switch($orderby):
                                    case ("price_low_high"): ?>
                                        <?php echo e(__("Price (Low to high)")); ?>

                                        <?php break; ?>
                                    <?php case ("price_high_low"): ?>
                                        <?php echo e(__("Price (High to low)")); ?>

                                        <?php break; ?>
                                    <?php default: ?>
                                        <?php echo e(__("Recommended")); ?>

                                <?php endswitch; ?>
                            </span>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
            <?php $param['orderby'] = "" ?>
            <a class="dropdown-item" href="<?php echo e(route("flight.search",$param)); ?>"><?php echo e(__("Recommended")); ?></a>
            <?php $param['orderby'] = "price_low_high" ?>
            <a class="dropdown-item" href="<?php echo e(route("flight.search",$param)); ?>"><?php echo e(__("Price (Low to high)")); ?></a>
            <?php $param['orderby'] = "price_high_low" ?>
            <a class="dropdown-item" href="<?php echo e(route("flight.search",$param)); ?>"><?php echo e(__("Price (High to low)")); ?></a>
        </div>
    </div>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Flight/Views/frontend/layouts/search/orderby.blade.php ENDPATH**/ ?>