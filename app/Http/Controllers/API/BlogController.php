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
