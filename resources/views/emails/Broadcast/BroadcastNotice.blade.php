@component('mail::message')
# Hello, Mr/Mrs {{$name}}

Ths is body of the Broadcast email.
Thanks,<br>
{{ config('app.name') }}
@endcomponent
