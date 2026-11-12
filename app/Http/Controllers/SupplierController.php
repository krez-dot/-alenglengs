<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->when($keyword !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$keyword}%")->orWhere('contact_person', 'like', "%{$keyword}%")
            ))
            ->orderBy('name')
            ->get();

        return view('suppliers.index', ['suppliers' => $suppliers, 'keyword' => $keyword]);
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => new Supplier()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete(); // products.supplier_id is ON DELETE SET NULL

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ], ['name.required' => 'Supplier name is required.']);
    }
}
