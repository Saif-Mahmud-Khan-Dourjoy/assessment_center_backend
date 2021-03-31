<?php

namespace Tests\Feature;

use App\Institute;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InstituteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Required field validation during Institute Creation throw API
     *
     * @return void
     */
    public function testRequiredFieldsForCreateInstitute()
    {
        $institute = Institute::create([
            'name' => $this->faker->name,
            'contact_no' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->domainName,
            'address' => $this->faker->address,
            'logo' => '',
            'icon' => '',
        ]);

        $this->actingAs(factory(User::class)->create([
            'username' => $this->faker->userName,
            'institute_id' => $institute->id
        ]), 'api');

        $instituteData = [];
        $this->json('POST', 'api/v1/institutes', $instituteData, ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "name" => ["The name field is required."]
                ]
            ]);
    }

    /**
     * test Institute create
     *
     * @return void
     */
    public function testInstituteCreatedSuccessfully()
    {
        $this->withoutExceptionHandling();
        $institute = Institute::create([
            'name' => $this->faker->name,
            'contact_no' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->domainName,
            'address' => $this->faker->address,
            'logo' => '',
            'icon' => '',
        ]);

        $this->actingAs(factory(User::class)->create([
            'username' => $this->faker->userName,
            'institute_id' => $institute->id
        ]), 'api');

        $instituteData = [
            'name' => 'Test Institute',
            'contact_no' => '0123456789',
            'email' => 'test@test.com',
            'website' => 'www.test.com',
            'address' => 'Dhaka',
            'logo' => '',
            'icon' => '',
        ];

        $this->json('POST', 'api/v1/institutes', $instituteData, ['Accept' => 'application/json'])
            ->assertStatus(200);
    }

    /**
     * Retrieve institute details data
     *
     * @return void
     */
    public function testRetrieveInstituteSuccessfully()
    {
        $this->withoutExceptionHandling();
        $institute = Institute::create([
            'name' => 'Test Institute',
            'contact_no' => '0123456789',
            'email' => 'test@test.com',
            'website' => 'www.test.com',
            'address' => 'Dhaka',
            'logo' => '',
            'icon' => '',
        ]);

        $this->actingAs(factory(User::class)->create([
            'username' => $this->faker->userName,
            'institute_id' => $institute->id
        ]), 'api');

        $this->json('GET', 'api/v1/institutes/' . $institute->id, [], ['Accept' => 'application/json'])
            ->assertStatus(200)
            /*->assertJson([
                "institutes" => [
                    [
                        'id' => 1,
                        'name' => 'Test Institute',
                        'contact_no' => '0123456789',
                        'email' => 'test@test.com',
                        'website' => 'www.test.com',
                        'address' => 'Dhaka',
                        'logo' => '',
                        'icon' => '',
                    ],
                ],
                "success" => true
            ])*/;
    }

    /**
     * Test Update Institute info
     *
     * @return void
     */
    public function testCEOUpdatedSuccessfully()
    {
        $institute = Institute::create([
            'name' => $this->faker->name,
            'contact_no' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->domainName,
            'address' => $this->faker->address,
            'logo' => '',
            'icon' => '',
        ]);

        $this->actingAs(factory(User::class)->create([
            'username' => $this->faker->userName,
            'institute_id' => $institute->id
        ]), 'api');

        $institute = Institute::create([
            'name' => 'Test Institute',
            'contact_no' => '0123456789',
            'email' => 'test@test.com',
            'website' => 'www.test.com',
            'address' => 'Dhaka',
            'logo' => '',
            'icon' => '',
        ]);

        $instituteData = [
            'name' => 'Test Institute 2',
            'contact_no' => '0123456789',
            'email' => 'test@test.com',
            'website' => 'www.test.com',
            'address' => 'Dhaka',
            'logo' => '',
            'icon' => '',
        ];

        $this->json('PATCH', 'api/v1/institutes/' . $institute->id , $instituteData, ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJson([
                "message" => 'Institute updated successfully!',
                "success" => true
            ]);
    }

    /**
     * Test Delete institute info
     *
     * @return void
     */
    public function testDeleteCEO()
    {
        $institute = Institute::create([
            'name' => $this->faker->name,
            'contact_no' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->domainName,
            'address' => $this->faker->address,
            'logo' => '',
            'icon' => '',
        ]);

        $this->actingAs(factory(User::class)->create([
            'username' => $this->faker->userName,
            'institute_id' => $institute->id
        ]), 'api');

        $institute = Institute::create([
            'name' => 'Test Institute',
            'contact_no' => '0123456789',
            'email' => 'test@test.com',
            'website' => 'www.test.com',
            'address' => 'Dhaka',
            'logo' => '',
            'icon' => '',
        ]);

        $this->json('DELETE', 'api/v1/institutes/' . $institute->id, [], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJson([
                "message" => 'Institute deleted',
                "success" => true
            ]);
    }
}
