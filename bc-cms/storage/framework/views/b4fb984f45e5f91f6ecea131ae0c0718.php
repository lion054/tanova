
<?php 
    $old = !empty($guests) ? $guests : 1;
?>
<div class="item" wire:ignore>
    <div class="searchMenu-guests  js-form-dd" x-data="{guests: <?php echo e($old); ?>,setGuests(guests){
                $wire.set('guests', guests, false);
                this.guests = guests;
            } }">
                                    <div data-x-dd-click="searchMenu-guests">
                                        <h4 class="text-15 fw-500 ls-2 lh-16"><?php echo e($field['title'] ?? ""); ?></h4>
                                        <div class="text-15 text-light-1 ls-2 lh-16">
                                            <span class="js-count-adult" x-text="guests"></span>
                                        </div>
                                    </div>
                                    <div class="searchMenu-guests__field shadow-2" data-x-dd="searchMenu-guests"
                                        data-x-dd-toggle="-is-active">
                                        <div class="bg-white px-30 py-30 rounded-4">
                                            <div class="row y-gap-10 justify-between items-center form-guest-search">
                                                <div class="col-auto">
                                                    <div class="text-15 fw-500"><?php echo e($field['title'] ?? ""); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="d-flex items-center">
                                                        <span
                                                            class="button -outline-blue-1 text-blue-1 size-38 rounded-4 js-down"
                                                            x-on:click="setGuests(Math.max(1, guests - 1))">
                                                            <i class="icon-minus text-12"></i>
                                                        </span>
                                                        <span class="flex-center size-20 ml-15 mr-15 count-display"><input type="number" name="room" x-bind:value="guests" min="1" class="has-value  text-center" x-on:change="setGuests(parseInt($event.target.value))"></span>
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

<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/themes/GoTrip/Visa/Views/frontend/layouts/search/fields/guests.blade.php ENDPATH**/ ?>