<?php
$minValue = 0;
?>
<div class="searchMenu-guests js-form-dd form-select-seat-type item">
    <div data-x-dd-click="searchMenu-guests" class="overflow-hidden seat-input">
        <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e($field['title']); ?></h4>

        <div class="text-15 text-light-1 ls-2 lh-16">
            <?php
                $seatTypeGet = request()->query('seat_type',[]);
            ?>
            <div class="render text-13">
            <?php if(!empty($seatType)): ?>
                <?php $__currentLoopData = $seatType; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                    $inputRender = 'seat_type_'.$type->code.'_render';
                    $inputValue = $seatTypeGet[$type->code] ?? $minValue;
                    ?>
                    <span class="" id="<?php echo e($inputRender); ?>">
                        <span class="one <?php if($inputValue > $minValue): ?> d-none <?php endif; ?>"><?php echo e(__( ':min :name',['min'=>$minValue,'name'=>$type->name])); ?></span>
                        <span class="<?php if($inputValue <= $minValue): ?> d-none <?php endif; ?> multi" data-html="<?php echo e(__(':count '.$type->name)); ?>"><?php echo e(__(':count'.$type->name,['count'=>$inputValue??$minValue])); ?></span>
                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="searchMenu-guests__field select-seat-type-dropdown shadow-2" data-x-dd="searchMenu-guests" data-x-dd-toggle="-is-active">
        <div class="bg-white px-30 py-30 rounded-4">
        <?php if(!empty($seatType)): ?>
            <?php $__currentLoopData = $seatType; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                $inputName = 'seat_type_'.$type->code;
                $inputValue = $seatTypeGet[$type->code] ?? $minValue;
                ?>

                <div class="row y-gap-10 justify-between items-center">
                    <div class="col-auto">
                        <div class="text-15 fw-500"><?php echo e(__('Adults :type',['type'=>$type->name])); ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex items-center">
                            <span class="button -outline-blue-1 text-blue-1 size-38 rounded-4 btn-minus" data-input="<?php echo e($inputName); ?>" data-input-attr="id"><i class="icon-minus text-12"></i></span>
                            <span class="flex-center size-20 ml-15 mr-15 count-display">
                                <input id="<?php echo e($inputName); ?>" type="number" name="seat_type[<?php echo e($type->code); ?>]" value="<?php echo e($inputValue); ?>" min="<?php echo e($inputValue); ?>">
                            </span>
                            <span class="button -outline-blue-1 text-blue-1 size-38 rounded-4 btn-add" data-input="<?php echo e($inputName); ?>" data-input-attr="id"><i class="icon-plus text-12"></i></span>
                        </div>
                    </div>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>

        </div>
    </div>


</div>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Layout/common/search/fields/seat_type.blade.php ENDPATH**/ ?>