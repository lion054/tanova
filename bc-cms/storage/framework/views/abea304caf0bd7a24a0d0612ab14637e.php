<?php $blocks_list = setting_item('cookie_consent_setting_modal_block_list');
$cookie_blocks_data = [];
if (!empty($blocks_list)){
    $blocks_list = json_decode($blocks_list);
    foreach ($blocks_list as $list){
        $data = [
            'title' => $list->title,
            'description' => $list->content
        ];
        if (!empty($list->toggle)){
            $data['toggle'] = [
                'value' => $list->value,
                'enabled' => (!empty($list->enable)),
                'readonly' => (!empty($list->readonly))
            ];
        }
        $cookie_blocks_data[] = $data;
    }
}
?>
<script defer src="<?php echo e(asset('libs/cookie-consent/cookieconsent.js')); ?>"></script>
<script>
    window.addEventListener('load', function () {

        // obtain plugin
        var cc = initCookieConsent();

        // run plugin with your configuration
        cc.run({
            current_lang: '<?php echo e(app()->getLocale()); ?>',
            autoclear_cookies: true,                   // default: false
            page_scripts: true,                        // default: false

            onFirstAction: function (user_preferences, cookie) {
                // callback triggered only once on the first accept/reject action
            },

            onAccept: function (cookie) {
                // callback triggered on the first accept/reject action, and after each page load
            },

            onChange: function (cookie, changed_categories) {
                // callback triggered when user changes preferences after consent has already been given
            },

            languages: {
                '<?php echo e(app()->getLocale()); ?>': {
                    consent_modal: {
                        title: '<?php echo e(setting_item('cookie_consent_title',__('We use cookies!'))); ?>',
                        description: '<?php echo clean(str_replace(array("\r", "\n"),'',setting_item('cookie_consent_desc'))); ?>',
                        primary_btn: {
                            text: '<?php echo e(setting_item('cookie_consent_primary_btn_text',__('Accept all'))); ?>',
                            role: '<?php echo e(setting_item('cookie_consent_primary_btn_text','accept_all')); ?>', // 'accept_selected' or 'accept_all'
                        },
                        secondary_btn: {
                            text: '<?php echo e(setting_item('cookie_consent_secondary_btn_text',__('Settings'))); ?>',
                            role: '<?php echo e(setting_item('cookie_consent_secondary_btn_text','settings')); ?>', // 'settings' or 'accept_necessary'
                        }
                    },
                    settings_modal: {
                        title: '<?php echo e(setting_item('cookie_consent_setting_modal_title',__('Cookie preferences'))); ?>',
                        save_settings_btn: '<?php echo e(setting_item('cookie_consent_setting_modal_save',__('Save settings'))); ?>',
                        accept_all_btn: '<?php echo e(setting_item('cookie_consent_setting_modal_accept',__('Accept all'))); ?>',
                        reject_all_btn: '<?php echo e(setting_item('cookie_consent_setting_modal_reject',__('Reject all'))); ?>',
                        close_btn_label: '<?php echo e(__('Close')); ?>',
                        blocks: <?php echo json_encode($cookie_blocks_data); ?>

                    }
                }
            }
        });
    });
</script>

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Layout/parts/cookie-consent-init.blade.php ENDPATH**/ ?>