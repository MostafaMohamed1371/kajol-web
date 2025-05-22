<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use File;


class BrandController extends Controller
{


    public function show($id)
{
    try {
        $brand = Brand::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Brand not found',
            'error' => $e->getMessage()
        ], 404);
    }
}

public function brands(Request $request)
{
    $perPage = $request->input('per_page', 10); // Allow customizable pagination
    $brands = Brand::orderBy('id', 'DESC')->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $brands->items(),
        'meta' => [
            'current_page' => $brands->currentPage(),
            'per_page' => $brands->perPage(),
            'total' => $brands->total(),
            'last_page' => $brands->lastPage(),
        ]
    ]);
}

public function storeApi(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'slug' => 'required|unique:brands,slug',
        'image' => 'mimes:png,jpg,jpeg|max:2048'
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }
    
    try {
        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->slug);
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileExtension = $image->extension();
            $fileName = Carbon::now()->timestamp . '.' . $fileExtension;
            
            // Store the image (adjust path as needed)
            $path = $image->storeAs('brands', $fileName, 'public');
            
            // Generate thumbnail if needed
            $this->GenerateBrandThumbailImage($image, $fileName);
            
            $brand->image = $fileName;
        }
        
        $brand->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully',
            'data' => $brand
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create brand',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function edit($id)
{
    try {
        $brand = Brand::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Brand not found',
            'error' => $e->getMessage()
        ], 404);
    }
}

public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'slug' => 'required|unique:brands,slug,'.$id,
        'image' => 'sometimes|mimes:png,jpg,jpeg|max:2048'
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }
    
    try {
        $brand = Brand::findOrFail($id);
        $brand->name = $request->name;
        $brand->slug = $request->slug;
        
        if ($request->hasFile('image')) {
            // Delete old images if they exist
            $this->deleteBrandImages($brand->image);
            
            // Process new image
            $image = $request->file('image');
            $fileExtension = $image->extension();
            $fileName = Carbon::now()->timestamp . '.' . $fileExtension;
            
            $this->generateBrandThumbnail($image, $fileName);
            $brand->image = $fileName;
        }
        
        $brand->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully',
            'data' => $brand
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update brand',
            'error' => $e->getMessage()
        ], 500);
    }
}




public function destroy($id)
{
    try {
        $brand = Brand::findOrFail($id);

        // Delete associated images
        $this->deleteBrandImagesForDelete($brand->image);
        
        // Delete the brand record
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete brand',
            'error' => $e->getMessage()
        ], 500);
    }
}

private function deleteBrandImagesForDelete($imageName)
{
    if (!$imageName) return;

    $paths = [
        public_path('uploads/brands/'.$imageName),
        public_path('uploads/brands/thumbnail/'.$imageName)
    ];
    
    foreach ($paths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}

private function deleteBrandImages($fileName)
{
    $paths = [
        public_path('uploads/brands/'.$fileName),
        public_path('uploads/brands/thumbnail/'.$fileName)
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

private function generateBrandThumbnail($image, $fileName)
{
    // Create directories if needed
    $directories = [
        public_path('uploads/brands'),
        public_path('uploads/brands/thumbnail')
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Store original image
    $image->move(public_path('uploads/brands'), $fileName);
    
    // Create thumbnail using Intervention Image v3+
    $manager = new ImageManager(new Driver());
    $img = $manager->read(public_path('uploads/brands/'.$fileName));
    $img->cover(100, 100)->save(public_path('uploads/brands/thumbnail/'.$fileName));
}


public function GenerateBrandThumbailImage($image, $file_name)
{
    // Create directories if needed
    $thumbnailPath = public_path('uploads/brands/thumbnail');
    if (!file_exists($thumbnailPath)) {
        mkdir($thumbnailPath, 0755, true);
    }

    // Move original image
    $image->move(public_path('uploads/brands'), $file_name);

    // Create manager instance with GD driver
    $manager = new ImageManager(new Driver());
    
    // Read image - NOTE: 'make()' is now 'read()' in v3+
    $img = $manager->read(public_path('uploads/brands/'.$file_name));
    
    // Process and save thumbnail
    $img->cover(100, 100)->save($thumbnailPath.'/'.$file_name);
}


}
