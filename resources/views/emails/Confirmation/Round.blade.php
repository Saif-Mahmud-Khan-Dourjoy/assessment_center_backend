@component('mail::message')
# Dear {{$name}},<br>
{{$body}}<br>

{{--@component('mail::button', ['url' => ''])--}}
{{--Button Text--}}
{{--@endcomponent--}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
