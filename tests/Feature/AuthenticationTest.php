<?php

namespace Tests\Feature;

use App\Institute;
use App\RoleSetup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * User authentication test before login.
     *
     * @return void
     */
    public function testOnlyLoggedInUserCanGoHome()
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }

    /**
     * User authentication test after successful login.
     *
     * @return void
     */
    public function testOnlyAuthenticationUserCanGoHome()
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
        ]));
        $this->get('/home')->assertOk();
    }

    /**
     * Required field validation during Registration throw API
     *
     * @return void
     */
    public function testRequiredFieldsForRegistration()
    {
        $this->json('POST', 'api/v1/register', ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "username" => ["The username field is required."],
                    "email" => ["The email field is required."],
                    "password" => ["The password field is required."],
                    "c_password" => ["The c password field is required."],
                    "birth_date" => ["The birth date field is required."],
                ]
            ]);
    }

    /**
     * Confirm password validation check throw API
     *
     * @return void
     */
    public function testRepeatPassword()
    {
        $userData = [
            "username" => $this->faker->userName,
            "birth_date" => $this->faker->date('d/m/Y'),
            "email" => $this->faker->email,
            "password" => "demo12345"
        ];

        $this->json('POST', 'api/v1/register', $userData, ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "c_password" => ["The c password field is required."]
                ]
            ]);
    }

    /**
     * Successful User Registration throw API
     *
     * @return void
     */
    public function testSuccessfulRegistration()
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
        $role = Role::create(['name' => 'Admin']);
        $permissions = ['super-admin'];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        $permissions = Permission::pluck('id','id')->all();
        $role->syncPermissions($permissions);
        RoleSetup::create([
            'contributor_role_id' => 1,
            'student_role_id' => 1,
            'new_register_user_role_id' => 1,
        ]);

        $userData = [
            'institute_id' => $institute->id,
            'username' => $this->faker->userName,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'password' => '123456789',
            'c_password' => '123456789',
            'birth_date' => $this->faker->date('d/m/Y'),
        ];

        $this->json('POST', 'api/v1/register', $userData, ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    /**
     * Email password confirmation
     *
     * @return void
     */
    public function testMustEnterEmailAndPassword()
    {
        $this->json('POST', 'api/v1/login')
            ->assertStatus(401)
            ->assertJson([
                "success" => false,
                "message" => 'Username or Password may incorrect!',
            ]);
    }

    /**
     * Successful login throw API
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        $institute = Institute::create([
            'name' => $this->faker->domainWord,
            'contact_no' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'website' => $this->faker->domainName,
            'address' => $this->faker->address,
            'logo' => '',
            'icon' => '',
        ]);

        $email = $this->faker->email;
        factory(User::class)->create([
            'institute_id' => $institute->id,
            'email' => $email,
            'username' => $email,
            'password' => bcrypt('sample123'),
        ]);


        $loginData = ['username' => $email, 'password' => 'sample123'];

        $this->json('POST', 'api/v1/login', $loginData, ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonStructure([
                "user" => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'roles'
                ],
                "token"
            ]);

        //$this->assertAuthenticated();
    }
}
