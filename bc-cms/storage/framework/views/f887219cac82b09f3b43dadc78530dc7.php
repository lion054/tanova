<?php if(is_default_lang()): ?>
<div class="panel mb-4">
    <div class="panel-title"><strong><?php echo e(__('Claude AI (Anthropic)')); ?></strong></div>
    <div class="panel-body">
        <div class="form-group">
            <label><?php echo e(__('API Key')); ?></label>
            <div class="form-controls">
                <input type="password" name="anthropic_api_key"
                       value="<?php echo e(setting_item('anthropic_api_key')); ?>" class="form-control"
                       autocomplete="new-password" placeholder="sk-ant-...">
                <small class="form-text text-muted">Powers Tanova itinerary generation and the AI Concierge.</small>
            </div>
        </div>
        <div class="form-group">
            <label><?php echo e(__('Model')); ?></label>
            <div class="form-controls">
                <input type="text" name="anthropic_model"
                       value="<?php echo e(setting_item('anthropic_model', 'claude-haiku-4-5-20251001')); ?>" class="form-control">
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-title"><strong><?php echo e(__('Tanova Settings')); ?></strong></div>
    <div class="panel-body">
        <div class="form-group">
            <label><?php echo e(__('Trip Window (hours)')); ?></label>
            <div class="form-controls">
                <input type="number" name="tanova_window_hours" min="1" max="168"
                       value="<?php echo e(setting_item('tanova_window_hours', 24)); ?>" class="form-control" style="max-width:120px">
                <small class="form-text text-muted">How long AI-generated trips stay in Tanova before expiring.</small>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?php echo e(route('admin.integrations.hub')); ?>" class="btn btn-outline-primary">
        Manage all integrations →
    </a>
</div>
<?php endif; ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/pro/Integrations/Views/admin/settings.blade.php ENDPATH**/ ?>