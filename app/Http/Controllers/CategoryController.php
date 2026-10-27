<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::withCount('products')
            ->withSum('products as product_value', 'price')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'color' => $c->color,
                'is_active' => (bool) $c->is_active,
                'products_count' => (int) $c->products_count,
            ]);

        return Inertia::render('Category/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')],
            'color' => ['required', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);
        $data['slug'] = str()->slug($data['name']);
        $data['is_active'] = true;

        Category::create($data);

        return back()->with('success', 'Kategori '.$data['name'].' ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category->id)],
            'color' => ['required', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active' => ['boolean'],
        ]);
        $data['slug'] = str()->slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $category->update($data);

        return back()->with('success', 'Kategori '.$data['name'].' diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih berisi produk.');
        }
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
