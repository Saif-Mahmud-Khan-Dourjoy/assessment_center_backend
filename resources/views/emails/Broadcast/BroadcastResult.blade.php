@component('mail::message')
    # Dear {{$first_name}},
    Greetings from Assessment Center for your participation on {{$question_set_title}} at {{$start_time}}.
    @component('mail::table')
    |    Particular            |           Details                                 |
    |--------------------------|:---------------------------------------------------:|
    | Assessment Name          | {{$question_set_title}}                           |
    | Started at               | {{$start_time}}                                   |
    | Ended at                 | {{$end_time}}                                     |
    | Assessment Time Duration | {{$assessment_time}}                              |
    | Hosted By                | {{$institute_name}}                               |
    | Powered By               | Neural Semiconductor                              |
    | Name of participation    | {{$first_name}} {{$last_name}}                    |
    | Mark Obtained            | {{$marks}} out of {{$total_mark}}                 |
    | Percentage               | {{$percentage}}                                   |
    | Rank Obtain              | {{$position}} out of {{$number_of_participation}} |
    @endcomponent
    </div>
    Note:
    1. Rank is calculated based on the mark participant obtained.
    a. If two or more participant have same mark then lower time-taker will be top of them.
    b. In case of similar marks and time both participant will have same rank.
    Thanks,<br>
{{ config('app.name') }}
@endcomponent
