<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with('customer');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->search($q);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->string('payment')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('order_date', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->whereDate('order_date', '<=', $request->string('to')->toString());
        }

        $orders = $query->orderByDesc('order_date')
            ->paginate(15)
            ->withQueryString();

        $orders->getCollection()->transform(function (Order $o) {
            return [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'customer' => $o->customer->name,
                'order_date' => $o->order_date->format('d M Y'),
                'total' => (float) $o->total,
                'status' => $o->status,
                'status_label' => $o->status_label,
                'payment_status' => $o->payment_status,
                'payment_label' => $o->payment_label,
                'items_count' => $o->items()->count(),
            ];
        });

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'payment' => $request->string('payment')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'counts' => [
                'menunggu_konfirmasi' => Order::where('status', 'menunggu_konfirmasi')->count(),
                'diproses' => Order::where('status', 'diproses')->count(),
                'selesai' => Order::where('status', 'selesai')->count(),
                'belum_bayar' => Order::where('payment_status', 'belum_bayar')->count(),
                'dp' => Order::where('payment_status', 'dp')->count(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load('customer', 'items.product');

        return Inertia::render('Order/Show', [
            'order' => [
                'id' => $order->id,
                'invoice_no' => $order->invoice_no,
                'order_date' => $order->order_date->format('d M Y'),
                'status' => $order->status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_label' => $order->payment_label,
                'total' => (float) $order->total,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('d M Y H:i'),
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone,
                ],
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'description' => $i->description,
                    'product_id' => $i->product_id,
                    'code' => $i->product?->code,
                    'unit' => $i->unit,
                    'qty' => (float) $i->qty,
                    'unit_price' => (float) $i->unit_price,
                    'total' => (float) $i->total,
                ]),
            ],
            'statuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $this->validate($request, [
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
        ]);

        $order->update($data);

        if ($data['status'] === 'menunggu_konfirmasi' || $data['status'] === 'diproses') {
            AppNotification::create([
                'user_id' => $request->user()->id,
                'type' => 'order',
                'title' => 'Status pesanan diperbarui',
                'body' => $order->invoice_no.' ('.$order->customer->name.') → '.Order::STATUSES[$data['status']].'.',
                'link' => '/pesanan/'.$order->id,
            ]);
        }

        return back()->with('success', 'Status '.$order->invoice_no.' diperbarui.');
    }
}
