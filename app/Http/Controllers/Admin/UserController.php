<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kelola pengguna admin/staf (khusus role admin). */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        return view('admin.users.form', ['user' => User::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate($this->rules($user->id), $this->messages(), $this->attributes());

        $role = $data['role'];
        $active = $request->boolean('is_active');

        // Admin tidak boleh menurunkan/menonaktifkan dirinya sendiri.
        if ($user->id === $request->user()->id) {
            $role = $user->role;
            $active = true;
        }

        if ($user->isAdmin() && ($role !== 'admin' || ! $active) && $this->isLastActiveAdmin($user)) {
            return back()->withInput()->with('error', 'Tidak bisa: ini satu-satunya admin aktif.');
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role,
            'is_active' => $active,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isAdmin() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak bisa menghapus satu-satunya admin aktif.');
        }

        $user->delete();

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::where('role', 'admin')->where('is_active', true)->where('id', '!=', $user->id)->exists();
    }

    private function rules(?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'in' => 'Pilihan :attribute tidak valid.',
            'max' => ':attribute terlalu panjang.',
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata sandi',
            'role' => 'Peran',
        ];
    }
}
