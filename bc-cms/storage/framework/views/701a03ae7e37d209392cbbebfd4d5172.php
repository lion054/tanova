<?php
$bookingType = $booking->getMeta("booking_type");?>
<div class="table-responsive">
    <table class="table table-striped table-inverse mb-1">
        <tbody>
        <?php
        $array = [];
        $current_price = 0;
        $current_from = 0;
        foreach ($rows as $key => $item){
            if($bookingType == "by_day"){
                $item['to'] = $item['from'];
                $item['to_html'] = $item['from_html'];
            }
            if(empty($array)){
                $current_price = $item['price'];
                $current_from = $item['from'];
                $array[$item['from']] = $item;
                continue;
            }
            if($current_price == $item['price']){
                $array[$current_from]['to'] = $item['to'];
                $array[$current_from]['to_html'] = $item['to_html'];
            }else{
                $current_price = $item['price'];
                $current_from = $item['from'];
                $array[$item['from']] = $item;
            }
        }
        ?>
        <?php $__currentLoopData = $array; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $days = 0;
                if($bookingType == "by_day"){
                    $days = max(1,floor(($value['to'] - $value['from']) / DAY_IN_SECONDS)+1);
                }
                if($bookingType == "by_night"){
                    $days = max(1,floor(($value['to'] - $value['from']) / DAY_IN_SECONDS));
                }
            ?>
            <tr>
                <td><?php echo e($value['from_html']); ?> <i class="fa fa-long-arrow-right"></i> <?php echo e($value['to_html']); ?></td>
                <td class="text-right"><?php echo e($value['price_html']); ?>*<?php echo e($days); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/Base/Space/Views/frontend/booking/detail-date.blade.php ENDPATH**/ ?>