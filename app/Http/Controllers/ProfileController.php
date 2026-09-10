<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private AuditLogService $auditLogs) {}

    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $changingPassword = $request->filled('password');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];

        if ($user->isOwner()) {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ];
        }

        $validated = $request->validate($rules);

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($user->isOwner()) {
            $user->email = $validated['email'];
        }

        if ($changingPassword) {
            $user->password = $validated['password'];
        }

        if ($request->boolean('remove_photo')) {
            $this->deleteStoredPhoto($user);
            $user->photo = null;
        }

        if ($request->hasFile('photo')) {
            $this->deleteStoredPhoto($user);
            $user->photo = $request->file('photo')->store('profile-photos', 'public');
        }

        $user->save();

        $this->auditLogs->log(
            AuditLog::ACTION_PROFILE_UPDATED,
            "{$user->name} updated their profile.",
            $user,
        );

        if ($changingPassword) {
            $this->auditLogs->log(
                AuditLog::ACTION_PASSWORD_CHANGED,
                "{$user->name} changed their password.",
                $user,
            );
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    private function deleteStoredPhoto(User $user): void
    {
        if (! filled($user->photo)) {
            return;
        }

        if (Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }
    }
}
