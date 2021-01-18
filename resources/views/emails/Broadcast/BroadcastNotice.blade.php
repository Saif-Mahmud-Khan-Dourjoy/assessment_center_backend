@component('mail::message')
# Dear {{$name}}
{{$body}}
Thanks,<br>
{{ config('app.name') }}
@endcomponent
