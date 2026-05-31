@component('mail::message')
# API Key Rotated

Hello {{ $vendorName }},

Your API key **"{{ $keyName }}"** has been successfully rotated.

**A new key was generated.** If you did not copy it at the time of rotation, you will need to log into the Tsoka portal and rotate it again to receive a new one.

@component('mail::panel')
If you did **not** initiate this rotation, please contact support immediately and revoke all your API keys from the portal.
@endcomponent

@component('mail::button', ['url' => url('/vendor/portal/api-keys')])
Manage API Keys
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
