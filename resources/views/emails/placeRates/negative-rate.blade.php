@component('mail::message')

Hello,<br>
A user in your country posted a negative review,

Comment:<br> 
"{{ $placeEvaluation->comment ?? '- The review has no comment -' }}"

Place Email:<br>
{{ $email ?? '- No email defined -' }}

@component('mail::button', [
    'url' => $url, 
    'color' => 'map4accessibility'
])
Check Comments
@endcomponent

Thanks,<br>
Map4Accessibility
@endcomponent
