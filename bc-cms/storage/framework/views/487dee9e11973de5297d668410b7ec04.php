<?php  $languages = \Modules\Language\Models\Language::getActive();  ?>
<?php if(is_default_lang()): ?>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Pricing")); ?></strong></div>
    <div class="panel-body">
        <?php if(is_default_lang()): ?>
            <div class="row">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="control-label"><?php echo e(__("Price")); ?></label>
                        <input type="number" step="any" min="0" name="price" class="form-control" value="<?php echo e($row->price); ?>" placeholder="<?php echo e(__("Property Price")); ?>">
                    </div>
                </div>
                
            </div>
        <?php endif; ?>
        
        
        
    </div>
</div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Property/Views/admin/property/pricing.blade.php ENDPATH**/ ?>