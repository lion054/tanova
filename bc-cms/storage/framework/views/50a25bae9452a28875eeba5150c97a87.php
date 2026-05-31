<?php $__env->startSection('title', 'Setup Facebook Messenger'); ?>

<?php $__env->startSection('content'); ?>
<div class="setup-page">
    <div class="setup-header">
        <a href="<?php echo e(route('user.integrations.index')); ?>" class="btn btn-light">← Back</a>
        <h1>Facebook Messenger Setup</h1>
    </div>

    <div class="setup-content">
        <div class="setup-info">
            <div class="info-section">
                <h3>What You Need</h3>
                <ul>
                    <li>Facebook Business Account</li>
                    <li>Facebook Page</li>
                    <li>Meta App with Messenger product</li>
                    <li>Page Access Token</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Setup Steps</h3>
                <ol>
                    <li><strong>Go to Meta Developers</strong> → <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a></li>
                    <li><strong>Create a new App</strong> (type: Business)</li>
                    <li><strong>Add Messenger product</strong></li>
                    <li><strong>Connect your Facebook Page</strong></li>
                    <li><strong>Generate Page Access Token</strong></li>
                    <li><strong>Paste below</strong> and click Save</li>
                </ol>
            </div>
        </div>

        <div class="setup-form-container">
            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('user.integrations.facebook')); ?>" class="setup-form">
                <?php echo csrf_field(); ?>

                <div class="form-section">
                    <h4>Your Facebook Credentials</h4>

                    <div class="form-group mb-3">
                        <label for="facebook_page_id">Facebook Page ID *</label>
                        <input type="text"
                               id="facebook_page_id"
                               name="facebook_page_id"
                               class="form-control <?php $__errorArgs = ['facebook_page_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               placeholder="123456789"
                               value="<?php echo e(old('facebook_page_id', Auth::user()->facebook_page_id)); ?>"
                               required>
                        <small class="text-muted">Found in your Facebook Page settings (usually numeric)</small>
                        <?php $__errorArgs = ['facebook_page_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="facebook_access_token">Page Access Token *</label>
                        <input type="password"
                               id="facebook_access_token"
                               name="facebook_access_token"
                               class="form-control <?php $__errorArgs = ['facebook_access_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               placeholder="EAAxxxxxxx..."
                               value="<?php echo e(old('facebook_access_token')); ?>"
                               required>
                        <small class="text-muted">🔒 Your token is encrypted and stored securely</small>
                        <?php $__errorArgs = ['facebook_access_token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="facebook_app_id">Facebook App ID (Optional)</label>
                        <input type="text"
                               id="facebook_app_id"
                               name="facebook_app_id"
                               class="form-control"
                               placeholder="123456789"
                               value="<?php echo e(old('facebook_app_id')); ?>">
                        <small class="text-muted">For webhook security verification</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="facebook_app_secret">App Secret (Optional)</label>
                        <input type="password"
                               id="facebook_app_secret"
                               name="facebook_app_secret"
                               class="form-control"
                               placeholder="••••••••••••••••"
                               value="<?php echo e(old('facebook_app_secret')); ?>">
                        <small class="text-muted">🔒 For webhook signature verification</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit"
                                name="test_connection"
                                value="1"
                                class="btn btn-outline-primary">
                            Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save & Connect
                        </button>
                    </div>
                </div>

                <div class="info-box">
                    <strong>💡 Tip:</strong> Test your connection before saving to make sure all credentials are correct.
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.setup-page {
    padding: 20px;
}

.setup-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
}

.setup-header h1 {
    margin: 0;
    flex: 1;
    color: #333;
}

.setup-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    max-width: 1200px;
}

.setup-info {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.info-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #1877F2;
}

.info-section h3 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.info-section ul, .info-section ol {
    margin: 0;
    padding-left: 20px;
    color: #666;
    line-height: 1.8;
}

.info-section li {
    margin-bottom: 10px;
}

.setup-form-container {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 30px;
}

.setup-form {
    display: flex;
    flex-direction: column;
}

.form-section h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 16px;
}

.form-group label {
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    display: block;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.form-actions .btn {
    flex: 1;
}

.info-box {
    background: #E3F2FD;
    border: 1px solid #2196F3;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
    color: #1565C0;
    font-size: 13px;
    line-height: 1.6;
}

.info-box strong {
    display: block;
    margin-bottom: 5px;
}

@media (max-width: 1024px) {
    .setup-content {
        grid-template-columns: 1fr;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('vendor.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Vendor/Views/frontend/integrations/facebook.blade.php ENDPATH**/ ?>