<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Setting/Index', [
            'user' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'app' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'model_version' => \App\Models\PredictionRun::latest()->first()?->model_version ?? '—',
                'last_run' => \App\Models\PredictionRun::where('status', 'completed')->latest()->first()?->finished_at?->format('d M Y H:i') ?? '—',
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $this->validate($request, [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Kata sandi diubah.');
    }
}
