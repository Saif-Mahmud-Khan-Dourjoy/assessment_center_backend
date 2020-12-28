@component('mail::message')
## Welcom to NSL-Assessment Center,
Your access credentials are here, <br>
username: {{$username}} <br>
password: {{$password}} <br>
@component('mail::button', ['url' => $url]) 
Click to go, NSL-Assessment Center
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
