@component('mail::message')
# Hello {{$first_name}}

Your Assessment participation certificate is attached.


Thanks,<br>
{{ config('app.name') }}
@endcomponent
