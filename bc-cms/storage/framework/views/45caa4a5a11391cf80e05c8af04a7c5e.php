<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Page Search")); ?></h3>
        <p class="form-group-desc"><?php echo e(__('Config page search of your website')); ?></p>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__("General Options")); ?></strong></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="" ><?php echo e(__("Title Page")); ?></label>
                    <div class="form-controls">
                        <input type="text" name="agency_page_search_title" value="<?php echo e(setting_item_with_lang('agency_page_search_title',request()->query('lang'))); ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if(is_default_lang()): ?>
    <hr>
    <div class="row">
        <div class="col-sm-4">
            <h3 class="form-group-title"><?php echo e(__("Review Options")); ?></h3>
            <p class="form-group-desc"><?php echo e(__('Config review for agency')); ?></p>
        </div>
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-body">
                    <div class="form-group">
                        <label class="" ><?php echo e(__("Enable review system for Agency?")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="agency_enable_review" value="1" <?php if(!empty(setting_item('agency_enable_review'))): ?> checked <?php endif; ?> /> <?php echo e(__("Yes, please enable it")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("Turn on the mode for reviewing agency")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="agency_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review must be approval by admin")); ?></label>
                        <div class="form-controls">
                            <label><input type="checkbox" name="agency_review_approved" value="1"  <?php if(!empty(setting_item('agency_review_approved'))): ?> checked <?php endif; ?> /> <?php echo e(__("Yes please")); ?> </label>
                            <br>
                            <small class="form-text text-muted"><?php echo e(__("ON: Review must be approved by admin - OFF: Review is automatically approved")); ?></small>
                        </div>
                    </div>
                    <div class="form-group" data-condition="agency_enable_review:is(1)">
                        <label class="" ><?php echo e(__("Review number per page")); ?></label>
                        <div class="form-controls">
                            <input type="number" class="form-control" name="agency_review_number_per_page" value="<?php echo e(setting_item('agency_review_number_per_page') ?? 5); ?>" />
                            <small class="form-text text-muted"><?php echo e(__("Break comments into pages")); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Agency/Views/admin/settings/agency.blade.php ENDPATH**/ ?>