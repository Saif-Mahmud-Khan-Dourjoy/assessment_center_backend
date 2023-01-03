<?php

use App\QuestionCategory;
use Illuminate\Database\Seeder;

class QuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
          $question_category=[
            [
                'name' => 'Advance',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'C++',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'PHP',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'Python',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'JAVA',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'JavaScript',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'Analog',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'DFT',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
            [
                'name' => 'PNR',
                'parents_id' => 0,
                'layer' => 0,
                'description' => NULL,
            ],
        ];

        foreach ($question_category as $category) {
            QuestionCategory::create($category);
        }
    }
}
