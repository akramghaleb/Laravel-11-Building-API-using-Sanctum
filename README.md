# Laravel 11 - Building API using Sanctum

##### Table of Contents 

- [Laravel 11 - Building API using Sanctum](#laravel-11---building-api-using-sanctum)
        - [Table of Contents](#table-of-contents)
  - [Step 1: Install Laravel 11](#step-1-install-laravel-11)
  - [Step 2: Install Sanctum API](#step-2-install-sanctum-api)
  - [Step 3: Sanctum Configuration](#step-3-sanctum-configuration)
  - [Step 4: Add Blog Migration and Model](#step-4-add-blog-migration-and-model)
  - [Step 5: Create Eloquent API Resources](#step-5-create-eloquent-api-resources)
  - [Step 6: Create Controller Files](#step-6-create-controller-files)
  - [Step 7: Create API Routes](#step-7-create-api-routes)
  - [Step 8: Run Laravel App](#step-8-run-laravel-app)
  - [Step 9: Check following API](#step-9-check-following-api)


## Step 1: Install Laravel 11

Open your terminal and Install new Laravel application

```
composer create-project laravel/laravel sanctum-api
```

Switch to the project folder

```
cd sanctum-api
```


## Step 2: Install Sanctum API

Run the following command to install Sanctum with API

```
php artisan install:api
```


## Step 3: Sanctum Configuration

In app/Models/User.php, we added the HasApiTokens class of Sanctum

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

## Step 4: Add Blog Migration and Model

Run the following command to add Blog migration and model

```
php artisan make:model Blog -m
```

After that go to database/migrations and you will find the created migration file

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('detail');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
```

Then go to app/Models/Blog.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'detail'
    ];
}
```

## Step 5: Create Eloquent API Resources

Run the following commands to create Blog API Resources

```
php artisan make:resource BlogResource
```

Then go to app/Http/Resources/BlogResource.php

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    // Transform the resource into an array.
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'detail' => $this->detail,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
        ];
    }
}
```

## Step 6: Create Controller Files

Run the following commands to add BaseController & RegisterController & BlogController

```
php artisan make:controller API/BaseController
php artisan make:controller API/RegisterController
php artisan make:controller API/BlogController
```

Then go to app/Http/Controllers/API/BaseController.php and add this code

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    // success response method
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    // return error response
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
```

Now go to app/Http/Controllers/API/BaseController.php

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController
{
    // Register api
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    // Login api
    public function login(Request $request): JsonResponse
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            $user = Auth::user();
            $success['token'] =  $user->createToken('MyApp')->plainTextToken;
            $success['name'] =  $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        }
        else{
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        }
    }
}
```

Finally, go to app/Http/Controllers/API/BlogController.php

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController;
use App\Models\Blog;
use App\Http\Resources\BlogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BlogController extends BaseController
{
    // Display a listing of the resource.
    public function index(): JsonResponse
    {
        $blogs = Blog::all();

        return $this->sendResponse(BlogResource::collection($blogs), 'Blogs retrieved successfully.');
    }

    // Store a newly created resource in storage.
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required',
            'detail' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $blog = Blog::create($input);

        return $this->sendResponse(new BlogResource($blog), 'Blog created successfully.');
    }

    // Display the specified resource.
    public function show($id): JsonResponse
    {
        $blog = Blog::find($id);

        if (is_null($blog)) {
            return $this->sendError('Blog not found.');
        }

        return $this->sendResponse(new BlogResource($blog), 'Blog retrieved successfully.');
    }

    // Update the specified resource in storage.
    public function update(Request $request, Blog $blog): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required',
            'detail' => 'required'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $blog->title = $input['title'];
        $blog->detail = $input['detail'];
        $blog->save();

        return $this->sendResponse(new BlogResource($blog), 'Blog updated successfully.');
    }

    // Remove the specified resource from storage.
    public function destroy(Blog $blog): JsonResponse
    {
        $blog->delete();

        return $this->sendResponse([], 'Blog deleted successfully.');
    }
}
```


## Step 7: Create API Routes

In this step we will create API routes for login, register, and blogs.

Go to routes/api.php

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\BlogController;

Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::apiResource('blogs', BlogController::class);
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('user');
});
```

## Step 8: Run Laravel App

Run the database migrations (Set the database connection in .env before migrating)

```
php artisan serve
```

Start the local development server

```
php artisan serve
```

## Step 9: Check following API

Now, go to your Postman to check api

Make sure in the details API, we will use the following headers as listed below

```json
'headers' => [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer '.$accessToken,
]
```

Now you can simply run the above listed URLs as shown in the screenshot below:

| Postman                        |
|-------------------------------------|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/01.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/02.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/03.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/04.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/05.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/06.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/07.png)|
|![Postman](https://raw.githubusercontent.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/main/scs/08.png)|

[Note: You can download postman file from here](https://github.com/akramghaleb/Laravel-11-Building-API-using-Sanctum/blob/main/postman/Laravel%20Sanctum.postman_collection.json)

[Github Repo](https://github.com/akramghaleb/Laravel-11-Building-API-using-Sanctum)

Thanks,

If you enjoy my work, consider buying me a coffee to keep the creativity flowing!

<a href="https://www.buymeacoffee.com/akramghaleb" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-red.png" alt="Buy Me A Coffee" width="150" ></a>
