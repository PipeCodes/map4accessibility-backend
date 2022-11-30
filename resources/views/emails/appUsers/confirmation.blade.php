@component('mail::message')

Confirm the email to be able to access your user account!

@component('mail::button', ['url' => $url, 'color' => 'map4accessibility'])
Confirm Email
@endcomponent

Did your confirmation expired? Click the button below to send a new email confirmation.

@component('mail::button', [
    'url' => $resendUrl, 
    'color' => 'map4accessibility-secondary'
])
Resend Confirmation
@endcomponent

Thanks,<br>
Map4Accessibility
@endcomponent
