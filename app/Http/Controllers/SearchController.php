<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Pencarian produk global (header). Dipakai oleh dropdown pencarian.
     */
    public function index(Request $request)
    {
        $q = $request->string('q')->trim()->toString();

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Product::with('category')
            ->where('is_active', true)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category->name,
                'category_color' => $p->category->color,
                'unit' => $p->unit,
                'price' => (float) $p->price,
                'url' => '/produk/'.$p->id,
            ]);

        return response()->json(['results' => $results]);
    }
}
