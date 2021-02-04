@component('mail::message')
# Dear {{$first_name}},
Greetings from ***Neural Semiconductor - Assessment Center*** for your participation on **{{$question_set_title}}** at ***{{$start_time}}***.

@component('mail::table')
|    Particular            |           Details                                      |
|--------------------------|:-------------------------------------------------------|
| Assessment Name          | **{{$question_set_title}}**                            |
| Started at               | **{{$start_time}}**                                    |
| Ended at                 | **{{$end_time}}**                                      |
| Assessment Time Duration | **{{$assessment_time}}**                               |
| Hosted By                | **{{$institute_name}}**                                |
| Powered By               | ***Neural Semiconductor Limited***                     |
| Participant Name         | **{{$first_name}} {{$last_name}}**                     |
| Mark Obtained            | **{{$marks}} out of {{$total_mark}}**                  |
| Time Taken               | **{{$time_taken}} out of {{$total_time}} (minutes)**   |
| Percentage               | **{{$percentage}}%**                                   |
| Rank Obtain              | **{{$position}}   out of {{$number_of_participation}}**|
@endcomponent
**Note:
Rank is calculated based on the mark participant obtained.<br>
1. If two or more participant have same mark then lower time-taker will be top the of them.<br>
2. In case of similar marks and time, those participants will have same rank.<br><br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
