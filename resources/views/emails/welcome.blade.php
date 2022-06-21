@component('mail::message')
## Hello
Welcome to NSL-ASSESSMENT CENTER! We are glad you've joined
 the biggest educational community.

@component('mail::button', ['url' => ''])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
