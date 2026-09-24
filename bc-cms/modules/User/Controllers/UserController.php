<?php
namespace Modules\User\Controllers;

use App\Helpers\ReCaptchaEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Matrix\Exception;
use Modules\Boat\Models\Boat;
use Modules\Booking\Models\Service;
use Modules\Car\Models\Car;
use Modules\Event\Models\Event;
use Modules\Flight\Models\Flight;
use Modules\FrontendController;
use Modules\Hotel\Models\Hotel;
use Modules\Space\Models\Space;
use Modules\Tour\Models\Tour;
use Modules\User\Events\NewVendorRegistered;
use Modules\User\Events\UserSubscriberSubmit;
use Modules\User\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vendor\Models\VendorRequest;
use Validator;
use Modules\Booking\Models\Booking;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Modules\Booking\Models\Enquiry;
use Illuminate\Support\Str;

class UserController extends FrontendController
{
    use AuthenticatesUsers;

    protected $enquiryClass;
    private Booking $booking;

    public function __construct(Booking $booking, Enquiry $enquiry)
    {
        $this->enquiryClass = $enquiry;
        parent::__construct();
        $this->booking = $booking;
    }

    public function dashboard(Request $request)
    {
        $this->checkPermission('dashboard_vendor_access');
        $user_id = Auth::id();
        $data = [
            'cards_report'       => $this->booking->getTopCardsReportForVendor($user_id),
            'earning_chart_data' => $this->booking->getEarningChartDataForVendor(strtotime('monday this week'), time(), $user_id),
            'recent_bookings'    => $this->booking->getRecentBookings(10, $user_id),
            'page_title'         => __("Vendor Dashboard"),
            'breadcrumbs'        => [
                [
                    'name'  => __('Dashboard'),
                    'class' => 'active'
                ]
            ]
        ];
        return view('User::frontend.dashboard', $data);
    }

    public function reloadChart(Request $request)
    {
        $chart = $request->input('chart');
        $user_id = Auth::id();
        switch ($chart) {
            case "earning":
                $from = $request->input('from');
                $to = $request->input('to');
                return $this->sendSuccess([
                    'data' => $this->booking->getEarningChartDataForVendor(strtotime($from), strtotime($to), $user_id)
                ]);
                break;
        }
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        $data = [
            'dataUser'         => $user,
            'page_title'       => __("Profile"),
            'breadcrumbs'      => [
                [
                    'name'  => __('Setting'),
                    'class' => 'active'
                ]
            ],
            'is_vendor_access' => $this->hasPermission('dashboard_vendor_access')
        ];
        return view('User::frontend.profile', $data);
    }

    public function profileUpdate(Request $request){
        if(is_demo_mode()){
            return back()->with('error',"Demo mode: disabled");
        }
        $user = Auth::user();
        $messages = [
            'user_name.required'      => __('The User name field is required.'),
        ];
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name'  => 'required|max:255',
            'email'      => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'user_name'=> [
                'required',
                'max:255',
                'min:4',
                'string',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone'       => [
                'required',
                Rule::unique('users')->ignore($user->id)
            ],
        ],$messages);
        $input = $request->except('bio');
        $user->fill($input);
        $user->bio = clean($request->input('bio'));
        $user->birthday = date("Y-m-d", strtotime($user->birthday));
        $user->user_name = Str::slug( $request->input('user_name') ,"_");
        $user->save();
        return redirect()->back()->with('success', __('Update successfully'));
    }

    public function bookingHistory(Request $request)
    {
        $user_id = Auth::id();
        $data = [
            'bookings' => $this->booking->getBookingHistory($request->input('status'), $user_id),
            'statues'     => config('booking.statuses'),
            'breadcrumbs' => [
                [
                    'name'  => __('Booking History'),
                    'class' => 'active'
                ]
            ],
            'page_title'  => __("Booking History"),
        ];
        return view('User::frontend.bookingHistory', $data);
    }

    public function subscribe(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255'
        ]);

        if (ReCaptchaEngine::isEnable() and setting_item("user_enable_subscribe_recaptcha")) {
            $codeCapcha = $request->input('g-recaptcha-response');
            if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                return $this->sendError(__("Please verify the captcha"));
            }
        }

        $check = Subscriber::withTrashed()->where('email', $request->input('email'))->first();
        if ($check) {
            if ($check->trashed()) {
                $check->restore();
                return $this->sendSuccess([], __('Thank you for subscribing'));
            }
            return $this->sendError(__('You are already subscribed'));
        } else {
            $a = new Subscriber();
            $a->email = $request->input('email');
            $a->first_name = $request->input('first_name');
            $a->last_name = $request->input('last_name');
            $a->save();

            event(new UserSubscriberSubmit($a));

            return $this->sendSuccess([], __('Thank you for subscribing'));
        }
    }

    public function upgradeVendor(Request $request){
        $user = Auth::user();
        $vendorRequest = VendorRequest::query()->where("user_id",$user->id)->where("status","pending")->first();
        if(!empty($vendorRequest)){
            return redirect()->back()->with('warning', __("You have just done the become vendor request, please wait for the Admin's approved"));
        }
        // check vendor auto approved
        $vendorAutoApproved = setting_item('vendor_auto_approved');
         $dataVendor['role_request'] = setting_item('vendor_role');
        if ($vendorAutoApproved) {
            if ($dataVendor['role_request']) {
                $user->assignRole($dataVendor['role_request']);
            }
            $dataVendor['status'] = 'approved';
            $dataVendor['approved_time'] = now();
        } else {
            $dataVendor['status'] = 'pending';
        }
        $vendorRequestData = $user->vendorRequest()->save(new VendorRequest($dataVendor));
        try {
            event(new NewVendorRegistered($user, $vendorRequestData));
        } catch (Exception $exception) {
            Log::warning("NewVendorRegistered: " . $exception->getMessage());
        }
        return redirect()->back()->with('success', __('Request vendor success!'));
    }



    public function permanentlyDelete(Request $request){
        if(is_demo_mode()){
            return back()->with('error',"Demo mode: disabled");
        }
        if(!empty(setting_item('user_enable_permanently_delete')))
        {
            $user = Auth::user();
            \DB::beginTransaction();
            try {
                Service::where('author_id',$user->id)->delete();
                Tour::where('author_id',$user->id)->delete();
                Car::where('author_id',$user->id)->delete();
                Space::where('author_id',$user->id)->delete();
                Hotel::where('author_id',$user->id)->delete();
                Event::where('author_id',$user->id)->delete();
                Boat::where('author_id',$user->id)->delete();
                Flight::where('author_id',$user->id)->delete();
                $user->sendEmailPermanentlyDelete();
                $user->delete();
                \DB::commit();
                Auth::logout();
                if(is_api()){
                    return $this->sendSuccess([],'Deleted');
                }
                return redirect(route('home'));
            }catch (\Exception $exception){
                \DB::rollBack();
            }
        }
        if(is_api()){
            return $this->sendError('Error. You can\'t permanently delete');
        }
        return back()->with('error',__('Error. You can\'t permanently delete'));

    }

    public function sendBookingWhatsApp(Request $request, $id)
    {
        $request->validate([
            'phone'   => 'required|string|max:30',
            'message' => 'required|string|max:2000',
        ]);

        Booking::where('vendor_id', Auth::id())->findOrFail($id);

        $apiKey    = setting_item('vonage_api_key');
        $apiSecret = setting_item('vonage_api_secret');
        $from      = setting_item('vonage_whatsapp_from');

        if (!$apiKey || !$apiSecret || !$from) {
            return response()->json(['error' => 'Vonage credentials not configured (vonage_api_key, vonage_api_secret, vonage_whatsapp_from).'], 422);
        }

        $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));

        try {
            $client = new \GuzzleHttp\Client();
            $client->post('https://api.nexmo.com/v1/messages', [
                'auth'    => [$apiKey, $apiSecret],
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json'    => [
                    'channel'      => 'whatsapp',
                    'message_type' => 'text',
                    'to'           => $phone,
                    'from'         => $from,
                    'text'         => $request->input('message'),
                ],
            ]);
            return response()->json(['success' => true]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => $body], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendBookingEmail(Request $request, $id)
    {
        $request->validate([
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $booking   = Booking::where('vendor_id', Auth::id())->findOrFail($id);
        $apiKey    = setting_item('mailtrap_api_key');
        $fromEmail = setting_item('mailtrap_from_email', setting_item('admin_email', 'noreply@example.com'));
        $fromName  = setting_item('site_title', 'Tsoka Travel');

        if (!$apiKey) {
            return response()->json(['error' => 'Mailtrap API key not configured (mailtrap_api_key).'], 422);
        }

        $guestName = trim($booking->first_name . ' ' . $booking->last_name);

        try {
            $client = new \GuzzleHttp\Client();
            $client->post('https://send.api.mailtrap.io/api/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'to'      => [['email' => $request->input('email'), 'name' => $guestName]],
                    'from'    => ['email' => $fromEmail, 'name' => $fromName],
                    'subject' => $request->input('subject'),
                    'text'    => $request->input('message'),
                ],
            ]);
            return response()->json(['success' => true]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            return response()->json(['error' => $body], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function vendorBookings(Request $request)
    {
        $userId = Auth::id();
        $query  = Booking::where('vendor_id', $userId)
            ->where('status', '!=', 'draft')
            ->whereIn('object_model', array_merge(array_keys(get_bookable_services()), ['tanova_trip']));

        $everything = (clone $query)->count();
        \App\Support\ListQuery::search($query, $request->query('s'), ['first_name', 'last_name', 'email', 'phone', 'code'], 'id');

        $statuses = (array) config('booking.statuses');
        $statusOpts = [];
        foreach ($statuses as $st) { $statusOpts[$st] = booking_status_to_text($st); }
        if (isset($statusOpts[(string) $request->query('status')])) { $query->where('status', $request->query('status')); }

        $services = [];
        foreach (array_keys(get_bookable_services()) as $type) { $services[$type] = ucfirst(str_replace('_', ' ', $type)); }
        $services['tanova_trip'] = __('Tanova trip');
        if (isset($services[(string) $request->query('service')])) { $query->where('object_model', $request->query('service')); }

        if ($from = \App\Support\ListQuery::date($request->query('from'))) { $query->whereDate('start_date', '>=', $from); }
        if ($to = \App\Support\ListQuery::date($request->query('to'))) { $query->whereDate('start_date', '<=', $to); }

        \App\Support\ListQuery::sort($query, $request->query('sort'), ['newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], 'trip' => ['start_date', 'asc'], 'trip_late' => ['start_date', 'desc'], 'amount' => ['total', 'desc']], 'newest');
        $fb = \App\Support\FilterBar::make($request)->search('s', __('Search guest, e-mail, code or number'))
            ->select('status', __('Status'), $statusOpts, __('Any status'))->select('service', __('Service'), $services, __('Any service'))->dates('from', 'to', __('Trip date'))
            ->sort(['newest' => __('Newest first'), 'oldest' => __('Oldest first'), 'trip' => __('Trip date, soonest'), 'trip_late' => __('Trip date, latest'), 'amount' => __('Biggest amount')], 'newest')
            ->perPage()->noun(__('bookings'))->total((clone $query)->reorder()->count(), $everything)->toArray();

        $totalRev   = (clone $query)->sum('total');
        $totalPaid  = (clone $query)->sum('paid');
        $processing = (clone $query)->where('status', 'processing')->count();

        return view('User::frontend.vendorBookings', [
            'rows'        => $query->paginate(\App\Support\ListQuery::perPage($request))->withQueryString(),
            'fb'          => $fb,
            'total_rev'   => $totalRev,
            'total_paid'  => $totalPaid,
            'processing'  => $processing,
            'statuses'    => config('booking.statuses'),
            'page_title'  => __('Bookings'),
        ]);
    }

}
