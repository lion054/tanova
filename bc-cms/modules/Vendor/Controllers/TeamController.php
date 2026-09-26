<?php
namespace Modules\Vendor\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\FrontendController;
use Modules\Vendor\Events\VendorTeamRequestCreatedEvent;
use Modules\Vendor\Models\VendorTeam;
use Modules\Vendor\Services\StaffAccess;

/**
 * A company's staff: employees who work in this company and see only its things. The owner adds a person, ticks the parts of the
 * portal they may use (config/staff_access.php), and can change or remove them at any time. Owner-only: staff cannot open this page
 * (see ActAsCompany). A person belongs to exactly one company.
 */
class TeamController extends FrontendController
{
    public function index()
    {
        $rows = VendorTeam::where('vendor_id', auth()->id())->with('member')->orderByDesc('id')->paginate(30);

        return view('Vendor::frontend.team.index', ['page_title' => __('Team members'), 'rows' => $rows, 'modules' => StaffAccess::modules(),
            'breadcrumbs' => [['name' => __('Team members')]]]);
    }

    public function add(Request $request)
    {
        $modules = array_keys(StaffAccess::modules());
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:191'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['in:' . implode(',', $modules)],
        ]);
        $owner = auth()->user();
        if (\Modules\Vendor\Services\CompanyOs::seatsLeft($owner) === 0) {
            return back()->withInput()->with('danger', __('Your plan\'s staff seats are all used. Remove someone, or move to a bigger plan under Plan & billing.'));
        }
        $email = strtolower(trim($data['email']));
        if (strtolower((string) $owner->email) === $email) {
            return back()->withInput()->with('danger', __('You cannot add yourself.'));
        }

        $member = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($member) {
            // A company owner or a platform account cannot become someone's staff, and nobody works for two companies.
            if ($member->hasPermission('dashboard_access') || $member->hasPermission('dashboard_vendor_access')) {
                return back()->withInput()->with('danger', __('That email belongs to an account that already runs a company or the platform, so it cannot join your team.'));
            }
            if (VendorTeam::where('member_id', $member->id)->exists()) {
                return back()->withInput()->with('danger', __('That person already works for a company. Nobody can be on two teams.'));
            }
        } else {
            $member = new User();
            $parts = explode(' ', trim($data['name']), 2);
            $member->name = trim($data['name']);
            $member->first_name = $parts[0];
            $member->last_name = $parts[1] ?? '';
            $member->email = $email;
            $member->password = Hash::make(Str::random(40));   // they set their own from the invitation link
            $member->status = 'publish';
            $member->email_verified_at = now();
            $member->role_id = (int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id');
            $member->save();
        }

        $team = new VendorTeam();
        $team->vendor_id = $owner->id;
        $team->member_id = $member->id;
        $team->status = setting_item('vendor_team_auto_approved') ? VendorTeam::STATUS_PUBLISH : VendorTeam::STATUS_PENDING;
        $team->permissions = array_values($data['permissions']);
        $team->save();
        VendorTeamRequestCreatedEvent::dispatch($team);

        return back()->with('success', __('Invitation sent to :e.', ['e' => $email]));
    }

    public function edit($id)
    {
        $team = $this->mine($id);

        return view('Vendor::frontend.team.edit', ['page_title' => __('Edit team member'), 'team' => $team, 'modules' => StaffAccess::modules(),
            'breadcrumbs' => [['name' => __('Team members'), 'url' => route('vendor.team.index')], ['name' => __('Edit')]]]);
    }

    public function store(Request $request, $id)
    {
        $team = $this->mine($id);
        $data = $request->validate(['permissions' => ['required', 'array', 'min:1'], 'permissions.*' => ['in:' . implode(',', array_keys(StaffAccess::modules()))]]);
        $team->permissions = array_values($data['permissions']);
        $team->save();

        return redirect(route('vendor.team.index'))->with('success', __('Access updated.'));
    }

    public function delete($id)
    {
        $team = $this->mine($id);
        $team->delete();   // the person's link to the company is cleared with it (see VendorTeam)

        return redirect(route('vendor.team.index'))->with('success', __('Removed from your team. They can no longer open your company.'));
    }

    public function reSendRequest(Request $request, $id)
    {
        VendorTeamRequestCreatedEvent::dispatch($this->mine($id));

        return back()->with('success', __('Invitation sent again.'));
    }

    /** The link in the invitation e-mail. */
    public function accept(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }
        if ($team = VendorTeam::find($request->input('vendor_team'))) {
            $team->status = VendorTeam::STATUS_PUBLISH;
            $team->save();
        }

        return redirect('/login')->with('status', __('You have joined the team. Sign in to start.'));
    }

    /** One of this company's own team records; anything else is "not found". */
    private function mine($id): VendorTeam
    {
        return VendorTeam::where('vendor_id', auth()->id())->findOrFail($id);
    }
}
