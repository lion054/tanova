<div>
    <form wire:submit.prevent="saveStep" class="bc-simple-form">
        <?php if(!empty($this->steps)): ?>
        <div class="row">
            <div class="col-md-3">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <?php $__currentLoopData = $this->steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index=>$step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button 
                            class="border-0 nav-link <?php echo e($index == $this->stepIndex ? 'active' : ''); ?> mb-2" 
                            <?php if($index > $this->maxStepIndex): ?> disabled <?php elseif($index !== $this->stepIndex): ?> wire:click="setStep('<?php echo e($index); ?>')" <?php endif; ?> 
                            id="<?php echo e($step['id']); ?>-tab" 
                            type="button" 
                            role="tab" 
                            aria-controls="<?php echo e($step['id']); ?>" 
                            aria-selected="<?php echo e($index == $this->stepIndex ? 'true' : 'false'); ?>"
                        >
                        <?php echo e($index + 1); ?>. <?php echo e($step['label']); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="col-md-9">
                <?php if($this->currentStep): ?>
                    <h4><?php echo e($this->currentStep['label']); ?></h4>
                    <hr>
                    <div class="row">
                        <?php $__currentLoopData = $this->currentStep['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('Form::frontend.simple-field', ['field' => $child], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary"><?php echo e(__('Next Step')); ?></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <?php $__currentLoopData = $this->form; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('Form::frontend.simple-field', ['field' => $field], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </form>
</div>

    <?php
        $__scriptKey = '254370348-1';
        ob_start();
    ?>
<script>
    Alpine.data("FormFilePicker", ()=> {
        return {
            loading: false,
            upload(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.loading = true;
                var me = $(event.target);
                var p = me.closest('.btn-upload-private-wrap');
                var lists = p.find('.private-file-lists');

                const fieldId = me.data('id');
                const options = me.data('options');

                const formData = new FormData();
                formData.append('file', file);
                formData.append('field_id', fieldId);

                if(options){
                    for (const key in options) {
                        formData.append('options[' + key + ']', options[key]);
                    }
                }

                $.ajax({
                    url: bookingCore.url + '/simple-form/upload-file',
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: (res) => {
                        this.loading = false;
                        me.val('');
                        if(res.data){
                            var div = $('<div/>');
                            div.addClass('col-md-3');

                            if(res.data.is_image){
                                div.append("<img style='max-width: 100%; height:auto;' src='" + res.data.download + "' alt='" + res.data.name + "'>");
                                div.append("<a target='_blank' href='" + res.data.download + "'> " + res.data.name + '.' + res.data.file_extension + " <i class=\"fa fa-download\"></i> </a>");
                            }

                            const keys = ['name', 'path', 'file_extension', 'file_type', 'size', 'driver'];
                            const dataToSave = keys.reduce((acc, key) => {
                                if (key in res.data) {
                                    acc[key] = res.data[key];
                                }
                                return acc;
                            }, {});
                            
                            if (me.data('multiple')) {
                                lists.append(div);

                                const old = $wire.get('data.' + fieldId);
                                $wire.set('data.' + fieldId, [...old, JSON.stringify(dataToSave)], false);
                            } else {
                                lists.html(div);
                                $wire.set('data.' + fieldId, JSON.stringify(dataToSave), false);
                            }
                        }

                        if(res.message){
                            alert(res.message);
                        }
                        
                    },
                    error: (e) => {
                        this.loading = false;
                        bookingCoreApp.showAjaxError(e);
                        me.val('');
                    }
                })
            }
        }
    })
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Form/Views/frontend/simple-form.blade.php ENDPATH**/ ?>