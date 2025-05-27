<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;
use Carbon\Carbon;
use File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $products = Product::with(['category', 'brand'])
                          ->orderBy('created_at', 'DESC')
                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * Get product creation data
     * GET /api/products/create
     */
    public function create()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'brands' => $brands
            ]
        ]);
    }

    /**
     * Store new product
     * POST /api/products
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'SKU' => 'required',
            'stock_status' => 'required|in:instock,outofstock',
            'featured' => 'required|boolean',
            'quantity' => 'required|integer',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'images.*' => 'sometimes|mimes:png,jpg,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = new Product();
            $this->saveProductData($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['category', 'brand'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product
     * GET /api/products/{id}
     */
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'brand'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get product edit data
     * GET /api/products/{id}/edit
     */
    public function edit($id)
    {
        try {
            $product = Product::with(['category', 'brand'])->findOrFail($id);
            $categories = Category::select('id', 'name')->orderBy('name')->get();
            $brands = Brand::select('id', 'name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'categories' => $categories,
                    'brands' => $brands
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update product
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|unique:products,slug,'.$id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'SKU' => 'required',
            'stock_status' => 'required|in:instock,outofstock',
            'featured' => 'required|boolean',
            'quantity' => 'required|integer',
            'image' => 'sometimes|mimes:png,jpg,jpeg|max:2048',
            'images.*' => 'sometimes|mimes:png,jpg,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($id);
            $this->saveProductData($product, $request);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['category', 'brand'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete product
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Delete associated images
            $this->deleteProductImages($product);
            
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to save product data (used by store and update)
     */
    private function saveProductData($product, $request)
    {
        $product->name = $request->name;
        $product->slug = Str::slug($request->slug);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $currentTimestamp = Carbon::now()->timestamp;

        // Handle main image
        if ($request->hasFile('image')) {
            // Delete old images if they exist
            if ($product->image) {
                $this->deleteImageFiles($product->image);
            }

            $image = $request->file('image');
            $fileName = $currentTimestamp . '.' . $image->extension();
            
            $this->generateThumbnail($image, $fileName);
            $product->image = $fileName;
        }

        // Handle gallery images
        if ($request->hasFile('images')) {
            // Delete old gallery images if they exist
            if ($product->images) {
                $this->deleteGalleryImages($product->images);
            }

            $galleryArr = [];
            $allowedExtensions = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            
            foreach ($files as $index => $file) {
                $extension = $file->extension();
                
                if (in_array($extension, $allowedExtensions)) {
                    $fileName = $currentTimestamp . '-' . ($index + 1) . '.' . $extension;
                    $this->generateThumbnail($file, $fileName);
                    $galleryArr[] = $fileName;
                }
            }
            
            $product->images = implode(',', $galleryArr);
        }

        $product->save();
    }

    /**
     * Helper method to generate thumbnails
     */
    private function generateThumbnail($image, $fileName)
    {
        $directories = [
            public_path('uploads/products'),
            public_path('uploads/products/thumbnails')
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Store original image
        $image->move(public_path('uploads/products'), $fileName);
        
        // Create thumbnail using Intervention Image
        $manager = new ImageManager(new Driver());
        $img = $manager->read(public_path('uploads/products/'.$fileName));
        $img->cover(300, 300)->save(public_path('uploads/products/thumbnails/'.$fileName));
    }

    /**
     * Helper method to delete product images
     */
    private function deleteProductImages($product)
    {
        // Delete main image
        if ($product->image) {
            $this->deleteImageFiles($product->image);
        }

        // Delete gallery images
        if ($product->images) {
            $this->deleteGalleryImages($product->images);
        }
    }

    /**
     * Helper method to delete single image files
     */
    private function deleteImageFiles($imageName)
    {
        $paths = [
            public_path('uploads/products/'.$imageName),
            public_path('uploads/products/thumbnails/'.$imageName)
        ];
        
        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    /**
     * Helper method to delete gallery images
     */
    private function deleteGalleryImages($imagesString)
    {
        $imageNames = explode(',', $imagesString);
        
        foreach ($imageNames as $imageName) {
            $this->deleteImageFiles(trim($imageName));
        }
    }
}
