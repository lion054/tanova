<div class="bc_search bc_search_tour">
    <?php if($layout == 'normal'): ?>
        <section class="pt-40 pb-40 bg-light-2">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="text-center">
                            <h1 class="text-30 fw-600"><?php echo e(setting_item_with_lang('visa_page_search_title')); ?></h1>
                        </div>
                        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('visa::search-form', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-610546739-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="layout-pt-md layout-pb-lg">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-lg-4">
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('visa::filter', ['lazy' => true]);

$__html = app('livewire')->mount($__name, $__params, 'lw-610546739-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                </div>
                <div class="col-xl-9 col-lg-8">
                    <?php echo $__env->make('Visa::frontend.layouts.search.list-item', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php $__env->startPush('js'); ?>
    <script type="text/javascript" src="<?php echo e(asset('js/filter.js?_ver=' . config('app.asset_version'))); ?>"></script>
    <script type="text/javascript" src="<?php echo e(asset('module/tour/js/tour.js?_ver=' . config('app.asset_version'))); ?>"></script>
<?php $__env->stopPush(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/index.blade.php ENDPATH**/ ?>