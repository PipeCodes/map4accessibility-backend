@component('mail::message')

Hello, {{ $email }},<br />
To reset your password, please click on the button below.

@component('mail::button', ['url' => $url, 'color' => 'map4accessibility'])
Reset Password
@endcomponent

Thanks,<br>
Map4Accessibility
@endcomponent
