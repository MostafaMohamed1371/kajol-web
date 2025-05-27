<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Carbon\Carbon;
use File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $categories = Category::orderBy('id', 'DESC')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * Create new category
     * POST /api/categories
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = new Category();
            $category->name = $request->name;
            $category->slug = Str::slug($request->slug);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $fileExtension = $image->extension();
                $fileName = Carbon::now()->timestamp . '.' . $fileExtension;
                
                $this->generateCategoryThumbnail($image, $fileName);
                $category->image = $fileName;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single category
     * GET /api/categories/{id}
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update category
     * PUT /api/categories/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,'.$id,
            'image' => 'sometimes|mimes:png,jpg,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = Category::findOrFail($id);
            $category->name = $request->name;
            $category->slug = $request->slug;

            if ($request->hasFile('image')) {
                // Delete old images
                $this->deleteCategoryImages($category->image);

                // Process new image
                $image = $request->file('image');
                $fileExtension = $image->extension();
                $fileName = Carbon::now()->timestamp . '.' . $fileExtension;
                
                $this->generateCategoryThumbnail($image, $fileName);
                $category->image = $fileName;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete category
     * DELETE /api/categories/{id}
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Delete associated images
            $this->deleteCategoryImages($category->image);
            
            // Delete the category
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to generate category thumbnail
     */
    private function generateCategoryThumbnail($image, $fileName)
    {
        $directories = [
            public_path('uploads/categories'),
            public_path('uploads/categories/thumbnail')
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Store original image
        $image->move(public_path('uploads/categories'), $fileName);
        
        // Create thumbnail using Intervention Image
        $manager = new ImageManager(new Driver());
        $img = $manager->read(public_path('uploads/categories/'.$fileName));
        $img->cover(100, 100)->save(public_path('uploads/categories/thumbnail/'.$fileName));
    }

    /**
     * Helper method to delete category images
     */
    private function deleteCategoryImages($imageName)
    {
        if (!$imageName) return;

        $paths = [
            public_path('uploads/categories/'.$imageName),
            public_path('uploads/categories/thumbnail/'.$imageName)
        ];
        
        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

}
