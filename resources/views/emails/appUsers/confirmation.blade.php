@component('mail::message')

Confirm the email to be able to access your user account!

@component('mail::button', ['url' => $url, 'color' => 'map4accessibility'])
Confirm Email
@endcomponent

Thanks,<br>
Map4Accessibility
@endcomponent
