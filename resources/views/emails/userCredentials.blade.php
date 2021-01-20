@component('mail::message')
## Dear {{$name}},
Greetings from NSL-Assessment Center. You are successfully registered to our system under the institution of Jahangirnagar Science Club for upcoming National Math Olympiad powered by Neural Semiconductor.
Please find your access credentials for login our system. <br>
username: {{$username}} <br>
password: {{$password}} <br>
@component('mail::button', ['url' => $url])
Click to go, NSL-Assessment Center
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
