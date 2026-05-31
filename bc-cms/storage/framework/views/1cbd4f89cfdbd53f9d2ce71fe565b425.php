

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('All Events')); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="filter-div row">
            <div class="div col-lg-12">
                <?php if($rows->count()): ?>
                    <form method="post" action="<?php echo e(route('tracking.admin.bulkEdit')); ?>" class="filter-form filter-form-left d-flex justify-content-start">
                        <?php echo e(csrf_field()); ?>

                        <select name="action" class="form-control">
                            <option value=""><?php echo e(__(" Bulk Actions ")); ?></option>
                            <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                        </select>
                        <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="col-left col-lg-12">
                <div class="panel mb-3">
                    <div class="panel-body">
                        <h5><?php echo e(__("Filter:")); ?></h5>
                <form method="get" class="mb-0 filter-form filter-form-right d-flex justify-content-start flex-column flex-sm-row" role="search">
                    <div id="reportrange" >
                        <input autocomplete="off" type="text" class="form-control" style="width: 200px;background: white" name="date_str" placeholder="-- Select Date --" value="<?php echo e(request('date_str')); ?>">
                        <input type="hidden" name="start_date" value="<?php echo e(request('start_date')); ?>">
                        <input type="hidden" name="end_date" value="<?php echo e(request('end_date')); ?>">
                    </div>
                    <?php
                    $user = !empty(Request()->vendor_id) ? App\User::find(Request()->vendor_id) : false;
                    \App\Helpers\AdminForm::select2('vendor_id', [
                        'configs' => [
                            'ajax'        => [
                                'url'      => url('/admin/module/user/getForSelect2'),
                                'dataType' => 'json',
                                'data' => array("user_type"=>"vendor")
                            ],
                            'allowClear'  => true,
                            'placeholder' => __('-- Vendor --')
                        ]
                    ], !empty($user->id) ? [
                        $user->id,
                        $user->getDisplayName() . ' (#' . $user->id . ')'
                    ] : false)
                    ?>
                    <select class="form-control" name="event_name">
                        <option value=""><?php echo e(__('-- Event Type --')); ?></option>
                        <option <?php if(request('event_name') == 'phone_click'): ?> selected <?php endif; ?> value="phone_click"><?php echo e(__("Phone Click")); ?></option>
                        <option <?php if(request('event_name') == 'website_click'): ?> selected <?php endif; ?> value="website_click"><?php echo e(__("Website Click")); ?></option>
                        <option <?php if(request('event_name') == 'enquiry_click'): ?> selected <?php endif; ?> value="enquiry_click"><?php echo e(__("Enquiry Click")); ?></option>
                        <option <?php if(request('event_name') == 'email_ads_click'): ?> selected <?php endif; ?> value="email_ads_click"><?php echo e(__("Email Ads Click")); ?></option>
                    </select>
                    <input data-condition="event_name:is(email_ads_click)" type="text" name="event_sub" class="form-control" placeholder="<?php echo e(__("Ads Name")); ?>" value="<?php echo e(request('event_sub')); ?>">
                    <select class="form-control" name="object_model">
                        <option value=""><?php echo e(__('-- Service Type --')); ?></option>
                        <option <?php if(request('object_model') == 'tour'): ?> selected <?php endif; ?> value="tour"><?php echo e(__("Tour")); ?></option>
                        <option <?php if(request('object_model') == 'hotel'): ?> selected <?php endif; ?> value="hotel"><?php echo e(__("Hotel")); ?></option>
                        <option <?php if(request('object_model') == 'event'): ?> selected <?php endif; ?> value="event"><?php echo e(__("Event")); ?></option>
                        <option <?php if(request('object_model') == 'campaign'): ?> selected <?php endif; ?> value="campaign"><?php echo e(__("Campaign")); ?></option>
                    </select>
                    <input type="text" name="object_id" class="form-control" placeholder="<?php echo e(__("Service ID")); ?>" value="<?php echo e(request('object_id')); ?>">
                    <button class="btn-info btn btn-icon btn_search" type="submit"><?php echo e(__('Search')); ?></button>
                </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right">
            <p><i><?php echo e(__('Found :total items',['total'=>$rows->total()])); ?></i></p>
        </div>
        <div class="panel">
            <div class="panel-body">
                <form action="" class="bc-form-item">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th><?php echo e(__('Event')); ?></th>
                                <th><?php echo e(__('Service')); ?></th>
                                <th><?php echo e(__('Lang')); ?></th>
                                <th><?php echo e(__('Country')); ?></th>
                                <th><?php echo e(__('Ip')); ?></th>
                                <th><?php echo e(__('Payout ID')); ?></th>
                                <th><?php echo e(__("CPC")); ?></th>
                                <th><?php echo e(__('Date')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo e($row->id); ?>" class="check-item"></td>
                                    <td class="title">
                                        <?php echo e($row->event_name); ?> <?php echo e($row->event_sub_name); ?>

                                    </td>
                                    <td><?php echo e(ucfirst($row->object_model).' #'.$row->object_id); ?> <a target="_blank" href="<?php echo e(route('tracking.go',['id'=>$row->id])); ?>"><i class="fa fa-eye"></i></a></td>
                                    <td><?php echo e($row->browser_lang); ?></td>
                                    <td><?php echo e($row->geoip_country_name); ?></td>
                                    <td><?php echo e($row->client_ip); ?></td>
                                    <td><?php echo e($row->payout_id  ? '#'.$row->payout_id : ''); ?></td>
                                    <td><?php echo e($row->cpc); ?></td>
                                    <td><?php echo e(display_datetime($row->created_at)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                <?php echo e($rows->appends(request()->query())->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script.body'); ?>

    <script src="<?php echo e(url('libs/daterange/moment.min.js')); ?>"></script>
    <script src="<?php echo e(url('libs/daterange/daterangepicker.min.js?_ver='.config('app.version'))); ?>"></script>
    <link rel="stylesheet" href="<?php echo e(url('libs/daterange/daterangepicker.css')); ?>"/>
    <script>
        <?php if(request('start_date') and request('end_date')): ?>
            var start = moment('<?php echo e(request('start_date')); ?>');
            var end = moment('<?php echo e(request('end_date')); ?>');
        <?php else: ?>
            var start = false;
            var end = false;
        <?php endif; ?>

        function cb(start, end) {
            $('#reportrange [name=date_str]').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            $('#reportrange input[name=start_date]').val(start.format('YYYY-MM-DD'));
            $('#reportrange input[name=end_date]').val(end.format('YYYY-MM-DD'));
        }

        $('#reportrange').daterangepicker({
            startDate: start ? start : false,
            endDate: end ? end : false,
            "alwaysShowCalendars": true,
            // "opens": "center",
            // "showDropdowns": true,
            autoUpdateInput: false,
            
            
            
            
            
            
            
            
            
            
        }, cb).on('apply.daterangepicker', function (ev, picker) {
            $('#reportrange input[name=start_date]').val(picker.startDate.format('YYYY-MM-DD'));
            $('#reportrange input[name=end_date]').val(picker.endDate.format('YYYY-MM-DD'));
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Tracking/Views/admin/index.blade.php ENDPATH**/ ?>