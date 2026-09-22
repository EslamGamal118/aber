<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        $query = Category::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('active', $status);
        }

        $categories = $query->paginate(10)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    // Show the form for creating a new resource.
    public function create()
    {
        return view('admin.categories.create');
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'active' => 'nullable|boolean',
        ]);

        Category::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'image' => $request->hasFile('image') ? $this->storeImage($request->file('image')) : null,
            'active' => $request->boolean('active'),
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully');
    }

    // Display the specified resource.
    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    // Show the form for editing the specified resource.
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    // Update the specified resource in storage.
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'active' => $request->boolean('active'),
            'slug' => Str::slug($validated['name']),
        ];

        if ($request->hasFile('image')) {
            // Store the new file before deleting the old one so a failed upload keeps the current image
            $newPath = $this->storeImage($request->file('image'));
            $category->deleteImageFile();
            $data['image'] = $newPath;
        } elseif ($request->boolean('remove_image')) {
            $category->deleteImageFile();
            $data['image'] = null;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully');
    }

    // Remove the specified resource from storage.
    public function destroy(Category $category)
    {
        $category->delete();
        $category->deleteImageFile();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully');
    }

    // Store an uploaded category image on the public disk and return its relative path.
    private function storeImage(UploadedFile $file): string
    {
        return $file->store(Category::IMAGE_DIRECTORY, 'public');
    }
}
