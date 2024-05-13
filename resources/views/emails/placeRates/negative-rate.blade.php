@component('mail::message')
    Hello,<br />
    A user in your country posted a negative review, <br />
    "{{ $placeEvaluation->comment }}"<br />
    <br />
    Place Email: <br />
    "{{ $email }}"

    @component('mail::button', ['url' => $url, 'color' => 'map4accessibility'])
        Check Comments
    @endcomponent

    Thanks,<br>
    Map4Accessibility
@endcomponent
