<div x-data="{step: $wire.get('stepIndex')}">
    <div class="row">
        <?php if(!empty($this->steps)): ?>
            <div class="col-md-3">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <?php $__currentLoopData = $this->steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button 
                            class="border-0 nav-link mb-2" 
                            x-on:click="step = '<?php echo e($index); ?>'" 
                            type="button" 
                            role="tab" 
                            x-bind:class="{ 'active': step == '<?php echo e($index); ?>' }"
                        >
                        <?php echo e($index + 1); ?>. <?php echo e($step['label']); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="col-md-9">
                <?php $__currentLoopData = $this->steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="card" x-show="step == '<?php echo e($index); ?>'">
                        <div class="card-body">
                            <?php $__currentLoopData = $step['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo $__env->make('Visa::frontend.components.applicant-form-view.field', ['field' => $child], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Visa/Views/frontend/components/applicant-form-view.blade.php ENDPATH**/ ?>