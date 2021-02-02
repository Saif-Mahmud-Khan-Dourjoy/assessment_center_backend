@component('mail::message')
# Dear {{$name}}
{{$body}}<br>
Thanks,<br>
{{ config('app.name') }}
@endcomponent
