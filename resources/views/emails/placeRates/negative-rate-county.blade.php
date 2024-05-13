@component('mail::message')

Olá,<br>
Um utilizador no teu concelho postou uma avaliação negativa,

Comentário:<br>
"{{ $placeEvaluation->comment ?? '- A avaliação não tem comentário -' }}"

Email do local:<br>
{{ $email ?? '- Email não definido -' }}

@component('mail::button', [   
    'url' => $url, 
    'color' => 'map4accessibility'
])
Ver Comentários
@endcomponent

Obrigado,<br>
Map4Accessibility
@endcomponent
