<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\ProfileImageManager;
use App\Support\SystemLogRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'],
        ]);

        if ($request->hasFile('profile_image')) {
            ProfileImageManager::delete($user->profile_image_path);
            $user->profile_image_path = ProfileImageManager::store($request->file('profile_image'));
        } elseif ($request->boolean('remove_profile_image')) {
            ProfileImageManager::delete($user->profile_image_path);
            $user->profile_image_path = null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        SystemLogRecorder::record(
            action: 'profile_updated',
            actor: $user,
            target: $user,
            request: $request,
            description: 'Profile details updated.'
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        SystemLogRecorder::record(
            action: 'profile_deleted',
            actor: $user,
            target: $user,
            request: $request,
            description: 'Profile/account deleted.'
        );

        ProfileImageManager::delete($user->profile_image_path);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
