<section class="pt-40">
    <div class="container">
        <div class="row y-gap-15 justify-between items-end">
            <div class="col-auto">
                <h1 class="text-30 fw-600"><?php echo clean($translation->title); ?></h1>
                <div class="row x-gap-20 y-gap-20 items-center pt-10">
                    <?php if(setting_item('visa_enable_review')): ?>
                        <div class="col-auto">
                            <?php $reviewData = $row->getScoreReview();
                            $score_total = $reviewData['score_total']; ?>
                            <?php echo $__env->make('Layout::common.rating', ['score_total' => $score_total], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="col-auto">
                            <div class="text-14 lh-14 text-light-1">
                                <?php if($reviewData['total_review'] > 1): ?>
                                    <?php echo e(__(':number Reviews', ['number' => $reviewData['total_review']])); ?>

                                <?php else: ?>
                                    <?php echo e(__(':number Review', ['number' => $reviewData['total_review']])); ?>

                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto">
                <div class="row x-gap-10 y-gap-10">
                    <div class="col-auto">
                        <div class="dropdown">
                            <button class="button px-15 py-10 -blue-1 dropdown-toggle" type="button"
                                id="dropdownMenuShare" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <i class="icon-share mr-10"></i>
                                <?php echo e(__('Share')); ?>

                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuShare">
                                <a class="dropdown-item facebook"
                                    href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>"
                                    target="_blank" rel="noopener" original-title="<?php echo e(__('Facebook')); ?>">
                                    <i class="fa fa-facebook"></i> <?php echo e(__('Facebook')); ?>

                                </a>
                                <a class="dropdown-item twitter"
                                    href="https://twitter.com/share?url=<?php echo e($row->getDetailUrl()); ?>&amp;title=<?php echo e($translation->title); ?>"
                                    target="_blank" rel="noopener" original-title="<?php echo e(__('X')); ?>">
                                    <i class="fa fa-twitter"></i> <?php echo e(__('X')); ?>

                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-auto">
                        <div class="service-wishlist <?php echo e($row->isWishList()); ?>" data-id="<?php echo e($row->id); ?>"
                            data-type="<?php echo e($row->type); ?>">
                            <button class="button px-15 py-10 -blue-1 bg-light-2">
                                <i class="icon-heart mr-10"></i>
                                <?php echo e(__('Save')); ?>

                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="pt-40 js-pin-container">
    <div class="container">
        <div class="row y-gap-30">
            <div class="col-lg-8">
                <div class="row y-gap-30  pt-20">
                    <?php if($row->to_country): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icon-globe text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Country')); ?>:<br> <?php echo e($row->country); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->visaType): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-beach text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Visa Type')); ?>:<br>
                                    <?php echo e($row->visaType->name ?? ''); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->code): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-code text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Code')); ?>:<br>
                                    <?php echo e($row->code ?? ''); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($row->processing_days): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Processing Days')); ?>:<br>
                                    <?php echo e($row->processing_days ?? ''); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if($row->max_stay_days): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Max Stay Days')); ?>:<br>
                                    <?php echo e(__(':amount day(s)', ['amount' => $row->max_stay_days])); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if($row->multiple_entry): ?>
                        <div class="col-md-3 col-6">
                            <div class="d-flex">
                                <i class="icofont-wall-clock text-22 text-blue-1 mr-10"></i>
                                <div class="text-15 lh-15">
                                    <?php echo e(__('Multiple Entry')); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="border-top-light mt-40 mb-40"></div>
                <?php if(!empty($translation->content)): ?>
                    <div class="row x-gap-40 y-gap-40 gotrip-overview">
                        <div class="col-12">
                            <h3 class="text-22 fw-500"><?php echo e(__('Overview')); ?></h3>
                            <div class="text-dark-1 text-15 mt-20 content-text">
                                <?php echo clean($translation->content); ?>

                            </div>
                            <span class="d-none btn-showmore pointer text-14 text-blue-1 fw-500 underline mt-10">
                                <?php echo e(__('Show More')); ?>

                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('visa::booking-form', ['row' => $row]);

$__html = app('livewire')->mount($__name, $__params, $row->id, $__slots ?? [], get_defined_vars());

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
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/layouts/details/visa-detail.blade.php ENDPATH**/ ?>