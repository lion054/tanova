<div class="pt-60 pb-60">
    <div class="row y-gap-40 justify-between xl:justify-start">
        <div class="col-xl-4 col-lg-6">
            @php $footerContent = setting_item_with_lang('footer_content_left'); @endphp

            <img src="{{ get_file_url(setting_item('logo_id')) }}" alt="image">
            @if(!empty($footerContent))
                @foreach(json_decode($footerContent) as $content)
                    {!! clean($content->content) !!}
                @endforeach
            @endif
        </div>

        <div class="col-lg-6">
            <div class="row y-gap-30">
                <div class="col-12">
                    <h5 class="text-16 fw-500 mb-15">{{ __('Get Updates & More') }}</h5>

                    <div class="pb-30 mailchimp">
                        <form action="{{route('newsletter.subscribe')}}" class="subcribe-form bc-subscribe-form bc-form single-field ">
                            @csrf
                           <div class='relative d-flex justify-end items-center'>
                                <input class="bg-white rounded-8 email-input" type="text" name="email" placeholder="{{__('Your Email')}}">
                                <button class="button absolute px-20 h-full text-15 fw-500 text-dark-1 underline">
                                    {{__('Subscribe')}} <i class="fa fa-spinner fa-pulse fa-fw"></i>
                                </button>
                            </div>
                                @if(setting_item("user_enable_subscribe_recaptcha"))
                                <div class='mt-2'>
                                    {{recaptcha_field( 'newsletter_subscribe')}}
                                </div>
                                @endif
                            <div class="d-flex justify-end ">
                                <div class="form-mess text-end"></div>
                            </div>
                        </form>
                    </div>
                </div>

                @if($list_widget_footers = setting_item_with_lang("footer_content_right"))
                    <?php $list_widget_footers = json_decode($list_widget_footers); ?>
                    @foreach($list_widget_footers as $key=>$item)
                        <div class="col-lg-4 col-sm-6">
                            <h5 class="text-16 fw-500 mb-30">{{ $item->title }}</h5>
                            {!! $item->content  !!}
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
