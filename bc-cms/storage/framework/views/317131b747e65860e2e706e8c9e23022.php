<?php $review_score = $row->review_data ?>
<div class="bc_single_book_wrap d-flex justify-end js-pin-content" data-offset="120">
    <div class="bc_single_book px-30 py-30 rounded-4 border-light bg-white w-360 lg:w-full">
        <?php echo $__env->make('Layout::common.detail.vendor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div id="bc_space_book_app" v-cloak>
            <div class="row y-gap-15 items-center justify-between">
                <div class="col-auto">
                    <div class="text-14 text-light-1">
                        <span class="text-14 text-red-1 line-through"><?php echo e($row->display_sale_price); ?></span>
                        <span class="text-20 fw-500 text-dark-1"><?php echo e($row->display_price); ?> </span> <?php echo e(__('nights')); ?>

                    </div>
                </div>

                <?php if($review_score): ?>
                    <div class="col-auto">
                        <div class="d-flex items-center">
                            <div class="text-14 text-right mr-10">
                                <div class="lh-15 fw-500"><?php echo e($review_score['score_text']); ?></div>
                                <div class="lh-15 text-light-1">
                                    <?php if($review_score['total_review'] > 1): ?>
                                        <?php echo e(__(':number reviews', ['number' => $review_score['total_review']])); ?>

                                    <?php else: ?>
                                        <?php echo e(__(':number review', ['number' => $review_score['total_review']])); ?>

                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="size-40 flex-center bg-blue-1 rounded-4">
                                <div class="text-14 fw-600 text-white"><?php echo e($review_score['score_total']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="nav-enquiry" v-if="is_form_enquiry_and_book">
                <div class="enquiry-item active">
                    <span><?php echo e(__('Book')); ?></span>
                </div>
                <div class="enquiry-item" data-toggle="modal" data-target="#enquiry_form_modal">
                    <span><?php echo e(__('Enquiry')); ?></span>
                </div>
            </div>
            <div class="form-book" :class="{ 'd-none': enquiry_type != 'book' }">
                <div class="form-content">
                    <div class="row y-gap-20 pt-20">
                        <div class="col-12">
                            <div class="form-group form-date-field form-date-search clearfix px-20 py-10 border-light rounded-4 -right position-relative"
                                data-format="<?php echo e(get_moment_date_format()); ?>">
                                <div class="date-wrapper clearfix" @click="openStartDate">
                                    <div class="check-in-wrapper">
                                        <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e(__('Select Dates')); ?></h4>
                                        <div class="render check-in-render" v-html="start_date_html"></div>
                                        <?php if(!empty($row->min_day_before_booking)): ?>
                                            <div class="render check-in-render">
                                                <small>
                                                    <?php if($row->min_day_before_booking > 1): ?>
                                                        -
                                                        <?php echo e(__('Book :number days in advance', ['number' => $row->min_day_before_booking])); ?>

                                                    <?php else: ?>
                                                        -
                                                        <?php echo e(__('Book :number day in advance', ['number' => $row->min_day_before_booking])); ?>

                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($row->min_day_stays)): ?>
                                            <div class="render check-in-render">
                                                <small>
                                                    <?php if($row->min_day_stays > 1): ?>
                                                        -
                                                        <?php echo e(__('Stay at least :number days', ['number' => $row->min_day_stays])); ?>

                                                    <?php else: ?>
                                                        -
                                                        <?php echo e(__('Stay at least :number day', ['number' => $row->min_day_stays])); ?>

                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <input type="text" class="start_date" ref="start_date"
                                    style="height: 1px;visibility: hidden;position: absolute;left: 0;">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="searchMenu-guests px-20 py-10 border-light rounded-4 js-form-dd">

                                <div data-x-dd-click="searchMenu-guests">
                                    <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e(__('Guest')); ?></h4>

                                    <div class="text-15 text-light-1 ls-2 lh-16">
                                        <span class="js-count-adult">{{ adults }}</span> <?php echo e(__('adults')); ?>

                                        -
                                        <span class="js-count-child">{{ children }}</span> <?php echo e(__('children')); ?>

                                    </div>
                                </div>

                                <div class="searchMenu-guests__field shadow-2" data-x-dd="searchMenu-guests"
                                    data-x-dd-toggle="-is-active">
                                    <div class="bg-white px-30 py-30 rounded-4">
                                        <div class="row y-gap-10 justify-between items-center form-guest-search">
                                            <div class="col-auto">
                                                <div class="text-15 fw-500"><?php echo e(__('Adults')); ?></div>
                                                <div class="text-14 lh-12 text-light-1 mt-5 render check-in-render">
                                                    <?php echo e(__('Ages 12+')); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="d-flex items-center js-counter"
                                                    data-value-change=".js-count-adult">
                                                    <button
                                                        class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-down"
                                                        @click="minusPersonType('adults')">
                                                        <i class="icon-minus text-12"></i>
                                                    </button>
                                                    <span class="input"><input type="number" v-model="adults"
                                                            min="1" /></span>
                                                    <button
                                                        class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-up"
                                                        @click="addPersonType('adults')">
                                                        <i class="icon-plus text-12"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border-top-light mt-24 mb-24"></div>

                                        <div class="row y-gap-10 justify-between items-center form-guest-search">
                                            <div class="col-auto">
                                                <div class="text-15 lh-12 fw-500"><?php echo e(__('Children')); ?></div>
                                                <div class="text-14 lh-12 text-light-1 mt-5 render check-in-render">
                                                    <?php echo e(__('Ages 2–12')); ?></div>
                                            </div>

                                            <div class="col-auto">
                                                <div class="d-flex items-center js-counter"
                                                    data-value-change=".js-count-child">
                                                    <button
                                                        class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-down"
                                                        @click="minusPersonType('children')">
                                                        <i class="icon-minus text-12"></i>
                                                    </button>
                                                    <span class="input"><input type="number" v-model="children"
                                                            min="0" /></span>
                                                    <button
                                                        class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-up"
                                                        @click="addPersonType('children')">
                                                        <i class="icon-plus text-12"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" v-if="extra_price.length">
                            <div class="form-section-group px-20 py-10 border-light rounded-4">
                                <h4 class="form-section-title text-15 fw-500 ls-2 lh-16"><?php echo e(__('Extra prices:')); ?></h4>
                                <div class="form-group " v-for="(type,index) in extra_price">
                                    <div class="extra-price-wrap d-flex justify-content-between">
                                        <div class="flex-grow-1">
                                            <label class="d-flex items-center">
                                                <span class="form-checkbox ">
                                                    <input type="checkbox" true-value="1" false-value="0"
                                                        v-model="type.enable" style="display: none">
                                                    <span class="form-checkbox__mark">
                                                        <span class="form-checkbox__icon icon-check"></span>
                                                    </span>
                                                </span>
                                                <span class="text-15 ml-10">{{ type.name }}</span>
                                            </label>
                                        </div>
                                        <div class="flex-shrink-0">{{ type.price_html }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" v-if="discount_by_days_output.length">
                            <div class="form-section-group form-group-padding px-20 py-10 border-light rounded-4">
                                <div class="extra-price-wrap d-flex justify-content-between">
                                    <div class="flex-grow-1">
                                        <label class="text-15">
                                            <?php echo e(__('Discounts:')); ?>

                                        </label>
                                    </div>
                                </div>
                                <div class="extra-price-wrap d-flex justify-content-between"
                                    v-for="(type,index) in discount_by_days_output">
                                    <div class="flex-grow-1">
                                        <label class="text-15" v-if='type.to'>
                                            {{ type.from }} - {{ type.to }}
                                            <?php echo e(setting_item('space_booking_type', 'by_day') == 'by_day' ? __('days') : __('night')); ?>

                                        </label>
                                        <label class="text-15" v-else>
                                            <?php echo e(__('from')); ?> {{ type.from }}
                                            <?php echo e(setting_item('space_booking_type', 'by_day') == 'by_day' ? __('day') : __('night')); ?>

                                        </label>
                                    </div>
                                    <div class="flex-shrink-0">
                                        - {{ formatMoney(type.total) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" v-if="buyer_fees.length">
                            <div class="form-section-group form-group-padding px-20 py-10 border-light rounded-4">
                                <div class="extra-price-wrap d-flex justify-content-between"
                                    v-for="(type,index) in buyer_fees">
                                    <div class="flex-grow-1">
                                        <label class="text-15">{{ type.type_name }}
                                            <i class="fa fa-info-circle" v-if="type.desc" data-bs-toggle="tooltip"
                                                data-placement="top" :title="type.type_desc"></i>
                                        </label>
                                        <div class="render" v-if="type.price_type">({{ type.price_type }})</div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="unit" v-if='type.unit == "percent"'>
                                            {{ type.price }}%
                                        </div>
                                        <div class="unit" v-else>
                                            {{ formatMoney(type.price) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" v-if="total_price > 0">
                            <ul class="form-section-total list-unstyled px-20 py-10 border-light rounded-4">
                                <li class="d-flex justify-content-between">
                                    <label class="text-15 fw-500"><?php echo e(__('Total')); ?></label>
                                    <span class="price">{{ total_price_html }}</span>
                                </li>
                                <li class="d-flex justify-content-between" v-if="is_deposit_ready">
                                    <label for=""><?php echo e(__('Pay now')); ?></label>
                                    <span class="price">{{ pay_now_price_html }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12" v-if="html">
                            <div v-html="html"></div>
                        </div>

                        <div class="col-12">
                            <div class="submit-group">
                                <div class="mb-5">
                                    <i>
                                        <?php if($row->max_guests <= 1): ?>
                                            <?php echo e(__(':count Guest in maximum', ['count' => $row->max_guests])); ?>

                                        <?php else: ?>
                                            <?php echo e(__(':count Guests in maximum', ['count' => $row->max_guests])); ?>

                                        <?php endif; ?>
                                    </i>
                                </div>
                                <a class="button -dark-1 py-15 px-35 h-60 col-12 rounded-4 bg-blue-1 text-white cursor-pointer"
                                    @click="doSubmit($event)"
                                    :class="{ 'disabled': onSubmit, 'btn-success': (step == 2), 'btn-primary': step == 1 }"
                                    name="submit">
                                    <span v-if="step == 1"><?php echo e(__('BOOK NOW')); ?></span>
                                    <span v-if="step == 2"><?php echo e(__('Book Now')); ?></span>
                                    <i v-show="onSubmit" class="fa fa-spinner fa-spin"></i>
                                </a>
                                <div class="alert-text text-danger mt-10" v-show="message.content"
                                    v-html="message.content" :class="{ 'danger': !message.type, 'success': message.type }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-send-enquiry mt-3 pt-3 mb-3" v-show="enquiry_type=='enquiry'">
                <button
                    class="button -dark-1 py-15 px-35 h-60 col-12 rounded-4 bg-blue-1 text-white cursor-pointer btn-primary"
                    data-toggle="modal" data-target="#enquiry_form_modal">
                    <?php echo e(__('Contact Now')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php echo $__env->make('Booking::frontend.global.enquiry-form', ['service_type' => 'space'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Space/Views/frontend/layouts/details/space-form-book.blade.php ENDPATH**/ ?>