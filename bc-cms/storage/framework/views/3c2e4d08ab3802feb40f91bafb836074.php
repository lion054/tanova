<div class="container-fluid">
    <div class="d-flex justify-content-between mb20">
        <h1 class="title-bar"><?php echo e(__('Edit Booking #:index',['index'=>$booking->id])); ?></h1>
    </div>
    <form wire:submit.prevent="submit">
    <div x-data="editBooking">
        <div class="row">
            <div class="col-md-9">
                <div class="panel">
                    <div class="panel-title">
                        <strong><?php echo e(__('Item Detail #:index',['index'=>$booking->id])); ?></strong>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><?php echo e(__('Select service')); ?></label>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select x-on:change="setObjectModel($event.target.value)" x-bind:value="object_model" class="form-control">
                                                <option value=""><?php echo e(__('-- Select Type --')); ?></option>
                                                <?php if(!empty(get_bookable_services())): ?>
                                                    <?php $__currentLoopData = get_bookable_services(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($id); ?>"><?php echo e(ucfirst($id)); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6" wire:ignore>
                                            <select x-on:change="setObjectId($event.target.value)" x-bind:value="object_id"  id="select-object" class="form-control">
                                                <?php if(!empty($this->service)): ?>
                                                    <option value="<?php echo e($this->service->id); ?>"><?php echo e($this->service->title); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <?php echo $__env->make('Booking::admin.booking.parts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
    </form>
</div>

    <?php
        $__scriptKey = '2700642787-0';
        ob_start();
    ?>
    <?php echo $__env->make('Booking::admin.booking.parts.script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Booking/Views/admin/booking/edit.blade.php ENDPATH**/ ?>