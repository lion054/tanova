<div class="col-12">
    <div class="form-group">
        <label for=""><?php echo e(__('Seat type')); ?></label>
        <?php
        $jsons = !empty($row->seatType) ? $row->seatType : false;
        \App\Helpers\AdminForm::select2('seat_type', [
                'configs' => [
                        'ajax'        => [
                                'url' => route('flight.admin.seat_type.getForSelect2'),
                                'dataType' => 'json'
                        ],
                        'allowClear'  => true,
                        'placeholder' => __('-- Select seat type --')
                ]
        ], !empty($jsons->id) ? [
                $jsons->code,
                $jsons->name . ' (#' . $jsons->id  . ')'
        ] : false)
        ?>
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label><?php echo e(__("Price")); ?> <span class="text-danger">*</span></label>
        <input type="number" required value="<?php echo e($row->price); ?>" min="1" placeholder="<?php echo e(__("Price")); ?>" name="price" class="form-control">
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label><?php echo e(__("Max passengers")); ?> <span class="text-danger">*</span></label>
        <input type="number" required value="<?php echo e($row->max_passengers ?? 1); ?>" min="1" max="100" placeholder="<?php echo e(__("Number")); ?>" name="max_passengers" class="form-control">
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label><?php echo e(__("Person type")); ?> <span class="text-danger">*</span></label>
        <select name="person" class="form-control" id="">
            <option <?php if($row->person=='adult'): ?> selected <?php endif; ?> value="adult"><?php echo e(__('Adult')); ?></option>
            <option <?php if($row->person=='child'): ?> selected <?php endif; ?> value="child"><?php echo e(__('Child')); ?></option>
        </select>
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label><?php echo e(__("Baggage Check in")); ?> <span class="text-danger">*</span></label>
        <input type="number" required value="<?php echo e($row->baggage_check_in ?? 1); ?>" min="1" max="100" placeholder="<?php echo e(__("Number")); ?>" name="baggage_check_in" class="form-control">
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label><?php echo e(__("Baggage Cabin")); ?> <span class="text-danger">*</span></label>
        <input type="number" required value="<?php echo e($row->baggage_cabin ?? 1); ?>" min="1" max="100" placeholder="<?php echo e(__("Number")); ?>" name="baggage_cabin" class="form-control">
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Flight/Views/admin/flight/seat/form.blade.php ENDPATH**/ ?>