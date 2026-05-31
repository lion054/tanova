<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Customer")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="mb-3" ><strong><?php echo e(__("Select Customer")); ?></strong></label>
                    <bc-select2 v-model="customer.id" :options="[{text:customer.display_name,id:customer.id}]" @select="selectCustomer" :settings="custom_select2" placeholder="<?php echo e(__("-- Please select --")); ?>"></bc-select2>
                    <a href="#" @click.prevent="reloadCustomerAddress" class="mt-1"><i><?php echo e(__('Copy customer address')); ?></i></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="mb-3"><strong><?php echo e(__("Billing")); ?></strong></div>
                    <address class="address">
                        <div v-for="key in address_keys" v-if="typeof billing[key] !='undefined' && billing[key]">
                            {{ key == 'country' ? countries[billing[key]] : billing[key] }}
                        </div>
                    </address>
                    <div v-if="billing.email"><strong ><?php echo e(__("Email:")); ?></strong> {{billing['email']}}</div>
                    <div v-if="billing.phone"><strong ><?php echo e(__("Phone:")); ?></strong> {{billing['phone']}}</div>

                    <a class="btn btn-default btn-sm" href="#" @click.prevent="editAddress('billing')"><i class="fa fa-edit"></i> <?php echo e(__("Edit")); ?></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="mb-3"><strong><?php echo e(__("Shipping Address")); ?></strong></div>
                    <address class="address">
                        <div v-for="key in address_keys" v-if="typeof shipping[key] !='undefined' && shipping[key]">
                            {{ key == 'country' ? countries[shipping[key]] : shipping[key] }}
                        </div>
                    </address>
                    <a class="btn btn-default btn-sm" href="#" @click.prevent="editAddress('shipping')"><i class="fa fa-edit"></i> <?php echo e(__("Edit")); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<bc-modal-address ref="modalAddress" @save="saveAddress"></bc-modal-address>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/order/detail/customer.blade.php ENDPATH**/ ?>