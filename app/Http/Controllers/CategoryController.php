<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', ['categories' => Category::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();
        } catch (QueryException $e) {
            return back()->with('danger', 'Cannot delete category: products are still assigned to it.');
        }

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category?->id)],
            'color' => ['nullable', Rule::in(['blue', 'orange', 'purple', 'yellow', 'teal', 'pink'])],
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with that name already exists.',
        ]);
        $data['color'] = $data['color'] ?? null;

        return $data;
    }
}
