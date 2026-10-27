<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::withCount('orders')
            ->withSum('orders as total_value', 'total');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->search($q);
        }

        $customers = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $customers->getCollection()->transform(fn (Customer $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'orders_count' => (int) $c->orders_count,
            'total_value' => (float) ($c->total_value ?? 0),
        ]);

        return Inertia::render('Customer/Index', [
            'customers' => $customers,
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function show(Request $request, Customer $customer): Response
    {
        $orders = $customer->orders()
            ->orderByDesc('order_date')
            ->paginate(10);

        $orders->getCollection()->transform(fn ($o) => [
            'id' => $o->id,
            'invoice_no' => $o->invoice_no,
            'order_date' => $o->order_date->format('d M Y'),
            'total' => (float) $o->total,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'payment_status' => $o->payment_status,
        ]);

        return Inertia::render('Customer/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'notes' => $customer->notes,
            ],
            'orders' => $orders,
            'summary' => [
                'total_orders' => $customer->orders()->count(),
                'total_value' => (float) $customer->orders()->sum('total'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:120', Rule::unique('customers', 'name')],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        Customer::create($data);

        return back()->with('success', 'Pelanggan '.$data['name'].' ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:120', Rule::unique('customers', 'name')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update($data);

        return back()->with('success', 'Data pelanggan diperbarui.');
    }
}
