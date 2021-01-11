@component('mail::message')
# Hello {{$first_name}},
Your Assessment score is published from the system. <br>
Assessment-title: {{$question_set_title}} <br>
Assessment-Duration:  **{{$assessment_time}}** <br>
Total-time taken: {{$time_taken}} <br>
Total-mark: **{{$marks}}**

Thanks,<br>
{{ config('app.name') }}
@endcomponent
