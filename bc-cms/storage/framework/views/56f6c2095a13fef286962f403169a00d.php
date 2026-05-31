<script type="text/x-template" id="bc-modal-address-template">
    <div class="modal fade" id="modal-address" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ title[type]}}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" @submit="save" method="get">
                    <div class="modal-body" >
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label><?php echo e(__('First name')); ?> <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="first_name" required value="" v-model="fields.first_name">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label><?php echo e(__('Last name')); ?> <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="last_name" required value="" v-model="fields.last_name">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3">
                                    <label><?php echo e(__('Company')); ?></label>
                                    <input class="form-control" type="text" name="company" value="" v-model="fields.company">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3">
                                    <label  class=""><?php echo e(__('Country / Region')); ?>&nbsp;<span class="text-danger" title="required">*</span></label>
                                    <div class="">
                                        <bc-select2 :options="countries" :settings="country_settings" v-model="fields.country"></bc-select2>
                                    </div>
                                    <div v-if="message_errors.country" class="error text-danger mt-1"> {{ message_errors.country }}</div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3 ">
                                    <label class="">
                                        <?php echo e(__('Street address')); ?>&nbsp;<span class="text-danger" title="required">*</span>
                                    </label>
                                    <input type="text" class="form-control " name="address" v-model="fields.address" placeholder="<?php echo e(__('House number and street name')); ?>" value="">
                                    <input type="text" class="form-control mt-3 " name="address2" v-model="fields.address2" placeholder="<?php echo e(__('Apartment, suite, unit, etc.')); ?>" value="">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label><?php echo e(__('City')); ?> <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="city" required value="" v-model="fields.city">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-3">
                                    <label><?php echo e(__('State')); ?></label>
                                    <input class="form-control" type="text" name="state" value="" v-model="fields.state">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3 ">
                                    <label class="">
                                        <?php echo e(__('Postcode / ZIP')); ?>

                                    </label>
                                    <input type="text" class="form-control " name="postcode"  value="" v-model="fields.postcode">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3 ">
                                    <label class="">
                                        <?php echo e(__('Phone')); ?> <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control " name="phone" required  value="" v-model="fields.phone">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group mb-3 ">
                                    <label class="">
                                        <?php echo e(__('Email')); ?> <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control " required  value="" v-model="fields.email">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo e(__('Close')); ?></button>
                        <button  class="btn btn-primary" type="submit"><?php echo e(__('Save changes')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</script>

<script>
    Vue.component('bc-modal-address', {
        template:'#bc-modal-address-template',
        data() {
            return {
                select2: null,
                title:{
                    billing:'<?php echo e(__("Billing address")); ?>',
                    shipping:'<?php echo e(__("Shipping address")); ?>',
                },
                fields:{},
                type:'billing',
                countries:[],
                active:0,
                country_settings:{
                    width:'100%',
                    allowClear  :true,
                },
                message_errors:{
                    country: ''
                }
            };
        },
        props: {
        },
        watch: {
        },
        methods: {
            save:function(e){
                e.preventDefault();
                if(this.fields.country == null){
                    this.message_errors.country = "<?php echo e(__("Please select a country!")); ?>";
                    return false;
                }
                this.$emit('save',this.type,Object.assign({},this.fields));
                this.hide();
            },
            show(type,fields){
                $('#modal-address').modal('show');
                this.type = type;
                this.fields = fields;
            },
            hide(){
                $('#modal-address').modal('hide');
            }
        },
        mounted() {
            var me = this;
            var tmp = [];
            for(var k in bc_country_list){
                tmp.push({
                    id:k,
                    text:bc_country_list[k]
                })
            }
            this.countries = tmp;
            this.$nextTick(function(){
                $('#modal-address').on('hide.bs.modal',function(){
                    me.active = 0
                })
            })
        },
        beforeDestroy() {
        }
    });
</script>
<?php /**PATH /home/lionel/Documents/Junkyard/gotrip/bc-cms/modules/Order/Views/admin/order/detail/components/modal-address.blade.php ENDPATH**/ ?>