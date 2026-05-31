<div x-data="bookingForm" class="bc_single_book_wrap d-flex justify-end js-pin-content" data-offset="120">
<form wire:submit.prevent="addToCart" class="bc_single_book">
    <div class="w-360 lg:w-full d-flex flex-column items-center">
        <div class="bc_single_book px-30 py-30 rounded-4 border-light bg-white shadow-4">
            <div id="bc_tour_book_app1" >
                <div class="row y-gap-15 items-center justify-between">
                    <div class="col-auto">
                        <div class="text-14 text-light-1">
                            <?php echo e(__('From')); ?>

                            <span class="text-14 text-red-1 line-through"><?php echo e(format_money($row->original_price)); ?></span>
                            <span class="text-20 fw-500 text-dark-1"><?php echo e(format_money($row->price)); ?></span>
                        </div>
                    </div>
                </div>
                <div class="nav-enquiry">
                    <div class="enquiry-item active">
                        <span><?php echo e(__('Book')); ?></span>
                    </div>
                </div>
                <div class="form-book">
                    <div class="form-content">
                        <div class="row y-gap-20 pt-20">
                            <div class="col-12">
                                <div class="searchMenu-guests px-20 py-10 border-light rounded-4 js-form-dd">
                                    <div data-x-dd-click="searchMenu-guests">
                                        <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e(__('Applications')); ?></h4>
                                        <div class="text-15 text-light-1 ls-2 lh-16">
                                            <span class="js-count-adult" x-text="guests"></span>
                                        </div>
                                    </div>
                                    <div class="searchMenu-guests__field shadow-2" data-x-dd="searchMenu-guests"
                                        data-x-dd-toggle="-is-active">
                                        <div class="bg-white px-30 py-30 rounded-4">
                                            <div class="row y-gap-10 justify-between items-center form-guest-search">
                                                <div class="col-auto">
                                                    <div class="text-15 fw-500"><?php echo e(__('Applications')); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="d-flex items-center js-counter"
                                                        data-value-change=".js-count-adult">
                                                        <span
                                                            class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-down"
                                                            x-on:click="setGuests(Math.max(1, guests - 1))">
                                                            <i class="icon-minus text-12"></i>
                                                        </span>
                                                        <span class="input"><input type="number" x-bind:value="guests" min="1"
                                        x-on:change="setGuests(parseInt($event.target.value))" /></span>
                                                        <span
                                                            class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-up"
                                                            x-on:click="setGuests(guests + 1)">
                                                            <i class="icon-plus text-12"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <template x-if="total_price > 0">
                                <div class="col-12">
                                    <ul class="form-section-total list-unstyled px-20 py-10 border-light rounded-4">
                                        <li class="d-flex justify-content-between">
                                            <label class="text-15 fw-500"><?php echo e(__('Total')); ?></label>
                                            <span class="price" x-text="bc_format_money(total_price)"></span>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                            <div class="col-12">
                                <div class="submit-group">
                                    <button type="submit" class="button -dark-1 py-15 px-35 h-60 col-12 rounded-4 bg-blue-1 text-white cursor-pointer"
                                        x-bind:class="{ 'disabled': total_price <= 0, 'btn-primary': total_price > 0 }"
                                        name="submit"
                                        x-bind:disabled="total_price <= 0">
                                        <span><?php echo e(__('Book Now')); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>
    <?php
        $__scriptKey = '1355477861-1';
        ob_start();
    ?>
    <script>
        Alpine.data('bookingForm', () => ({
            guests: $wire.entangle('guests'),
            bookingData: $wire.entangle('bookingData'),
            setGuests(guests){
                $wire.set('guests', guests, false);
                this.guests = guests;
            },
            get total_price_html(){
                return bc_format_money(this.total_price);
            },
            get total_price(){
                return this.bookingData.price * this.guests;
            }
        }));
    </script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/components/booking-form.blade.php ENDPATH**/ ?>