<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ProfileImageManager;
use App\Support\SystemLogRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public const SURETY_RELATIONS = [
        'spouse',
        'father',
        'mother',
        'brother',
        'sister',
        'son',
        'daughter',
        'friend',
        'other',
    ];

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.members.index');
    }

    public function membersIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $governmentIdFilter = (string) $request->query('govt_id', 'all');
        $sort = (string) $request->query('sort', 'latest');

        if (! in_array($governmentIdFilter, ['all', 'uploaded', 'missing'], true)) {
            $governmentIdFilter = 'all';
        }

        if (! in_array($sort, ['latest', 'oldest'], true)) {
            $sort = 'latest';
        }

        $membersQuery = User::query()->where('role', User::ROLE_USER);

        if ($search !== '') {
            $searchTerm = '%'.$search.'%';

            $membersQuery->where(function ($query) use ($searchTerm): void {
                $query
                    ->where('name', 'like', $searchTerm)
                    ->orWhere('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('mobile_number', 'like', $searchTerm)
                    ->orWhere('address', 'like', $searchTerm);
            });
        }

        if ($governmentIdFilter === 'uploaded') {
            $membersQuery->whereNotNull('government_id_path');
        } elseif ($governmentIdFilter === 'missing') {
            $membersQuery->whereNull('government_id_path');
        }

        if ($sort === 'oldest') {
            $membersQuery->oldest();
        } else {
            $membersQuery->latest();
        }

        $members = $membersQuery->paginate(9)->withQueryString();

        return view('admin.users.members.index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'govt_id' => $governmentIdFilter,
                'sort' => $sort,
            ],
            'totalMembers' => User::query()->where('role', User::ROLE_USER)->count(),
        ]);
    }

    public function editorsIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $profileFilter = (string) $request->query('profile_image', 'all');
        $sort = (string) $request->query('sort', 'latest');

        if (! in_array($profileFilter, ['all', 'uploaded', 'missing'], true)) {
            $profileFilter = 'all';
        }

        if (! in_array($sort, ['latest', 'oldest'], true)) {
            $sort = 'latest';
        }

        $editorsQuery = User::query()->where('role', User::ROLE_EDITOR);

        if ($search !== '') {
            $searchTerm = '%'.$search.'%';

            $editorsQuery->where(function ($query) use ($searchTerm): void {
                $query
                    ->where('name', 'like', $searchTerm)
                    ->orWhere('first_name', 'like', $searchTerm)
                    ->orWhere('last_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('mobile_number', 'like', $searchTerm);
            });
        }

        if ($profileFilter === 'uploaded') {
            $editorsQuery->whereNotNull('profile_image_path');
        } elseif ($profileFilter === 'missing') {
            $editorsQuery->whereNull('profile_image_path');
        }

        if ($sort === 'oldest') {
            $editorsQuery->oldest();
        } else {
            $editorsQuery->latest();
        }

        $editors = $editorsQuery->paginate(9)->withQueryString();

        return view('admin.users.editors.index', [
            'editors' => $editors,
            'filters' => [
                'search' => $search,
                'profile_image' => $profileFilter,
                'sort' => $sort,
            ],
            'totalEditors' => User::query()->where('role', User::ROLE_EDITOR)->count(),
        ]);
    }

    public function createMember(): View
    {
        return view('admin.users.members.create', [
            'relations' => self::SURETY_RELATIONS,
        ]);
    }

    public function storeMember(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->memberRules());

        $governmentIdPath = $request->file('government_id')?->store('government-ids');
        $profileImagePath = $request->hasFile('profile_image')
            ? ProfileImageManager::store($request->file('profile_image'))
            : null;

        $member = User::query()->create([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => Str::lower($validated['email']),
            'mobile_number' => $validated['mobile_number'],
            'primary_phone' => $validated['mobile_number'],
            'role' => User::ROLE_USER,
            'government_id_path' => $governmentIdPath,
            'profile_image_path' => $profileImagePath,
            'family_name' => $validated['surety_name'],
            'family_relation' => $validated['surety_relation'],
            'family_phone_number' => $validated['surety_phone_number'],
            'family_address' => $validated['surety_address'],
            'family_government_id' => $validated['surety_government_id'],
            'family_bank_name' => $validated['surety_bank_name'],
            'family_cheque_number' => $validated['surety_cheque_book_number'],
            'password' => Hash::make($validated['password']),
        ]);

        SystemLogRecorder::record(
            action: 'member_created',
            actor: $request->user(),
            target: $member,
            request: $request,
            description: 'Member created by admin.'
        );

        return redirect()->route('admin.members.index')->with('status', 'member-created');
    }

    public function editMember(User $user): View
    {
        $member = $this->requireRole($user, User::ROLE_USER);

        return view('admin.users.members.edit', [
            'member' => $member,
            'relations' => self::SURETY_RELATIONS,
        ]);
    }

    public function updateMember(Request $request, User $user): RedirectResponse
    {
        $member = $this->requireRole($user, User::ROLE_USER);

        $validated = $request->validate($this->memberRules($member));

        $governmentIdPath = $member->government_id_path;
        if ($request->hasFile('government_id')) {
            if ($governmentIdPath) {
                Storage::delete($governmentIdPath);
            }
            $governmentIdPath = $request->file('government_id')->store('government-ids');
        }

        $profileImagePath = $member->profile_image_path;
        if ($request->hasFile('profile_image')) {
            ProfileImageManager::delete($profileImagePath);
            $profileImagePath = ProfileImageManager::store($request->file('profile_image'));
        } elseif ($request->boolean('remove_profile_image')) {
            ProfileImageManager::delete($profileImagePath);
            $profileImagePath = null;
        }

        $payload = [
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => Str::lower($validated['email']),
            'mobile_number' => $validated['mobile_number'],
            'primary_phone' => $validated['mobile_number'],
            'government_id_path' => $governmentIdPath,
            'profile_image_path' => $profileImagePath,
            'family_name' => $validated['surety_name'],
            'family_relation' => $validated['surety_relation'],
            'family_phone_number' => $validated['surety_phone_number'],
            'family_address' => $validated['surety_address'],
            'family_government_id' => $validated['surety_government_id'],
            'family_bank_name' => $validated['surety_bank_name'],
            'family_cheque_number' => $validated['surety_cheque_book_number'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $member->update($payload);

        SystemLogRecorder::record(
            action: 'member_updated',
            actor: $request->user(),
            target: $member,
            request: $request,
            description: 'Member details updated by admin.'
        );

        return redirect()->route('admin.members.index')->with('status', 'member-updated');
    }

    public function destroyMember(User $user): RedirectResponse
    {
        $member = $this->requireRole($user, User::ROLE_USER);

        SystemLogRecorder::record(
            action: 'member_deleted',
            actor: request()->user(),
            target: $member,
            request: request(),
            description: 'Member deleted by admin.'
        );

        if ($member->government_id_path) {
            Storage::delete($member->government_id_path);
        }
        ProfileImageManager::delete($member->profile_image_path);

        $member->delete();

        return redirect()->route('admin.members.index')->with('status', 'member-deleted');
    }

    public function createEditor(): View
    {
        return view('admin.users.editors.create');
    }

    public function storeEditor(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->editorRules());
        $profileImagePath = $request->hasFile('profile_image')
            ? ProfileImageManager::store($request->file('profile_image'))
            : null;

        $editor = User::query()->create([
            'name' => $validated['name'],
            'first_name' => $validated['name'],
            'last_name' => null,
            'email' => Str::lower($validated['email']),
            'mobile_number' => $validated['mobile_number'],
            'primary_phone' => $validated['mobile_number'],
            'profile_image_path' => $profileImagePath,
            'role' => User::ROLE_EDITOR,
            'password' => Hash::make($validated['password']),
        ]);

        SystemLogRecorder::record(
            action: 'editor_created',
            actor: $request->user(),
            target: $editor,
            request: $request,
            description: 'Editor created by admin.'
        );

        return redirect()->route('admin.editors.index')->with('status', 'editor-created');
    }

    public function editEditor(User $user): View
    {
        $editor = $this->requireRole($user, User::ROLE_EDITOR);

        return view('admin.users.editors.edit', [
            'editor' => $editor,
        ]);
    }

    public function updateEditor(Request $request, User $user): RedirectResponse
    {
        $editor = $this->requireRole($user, User::ROLE_EDITOR);

        $validated = $request->validate($this->editorRules($editor));

        $profileImagePath = $editor->profile_image_path;
        if ($request->hasFile('profile_image')) {
            ProfileImageManager::delete($profileImagePath);
            $profileImagePath = ProfileImageManager::store($request->file('profile_image'));
        } elseif ($request->boolean('remove_profile_image')) {
            ProfileImageManager::delete($profileImagePath);
            $profileImagePath = null;
        }

        $payload = [
            'name' => $validated['name'],
            'first_name' => $validated['name'],
            'last_name' => null,
            'email' => Str::lower($validated['email']),
            'mobile_number' => $validated['mobile_number'],
            'primary_phone' => $validated['mobile_number'],
            'profile_image_path' => $profileImagePath,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $editor->update($payload);

        SystemLogRecorder::record(
            action: 'editor_updated',
            actor: $request->user(),
            target: $editor,
            request: $request,
            description: 'Editor details updated by admin.'
        );

        return redirect()->route('admin.editors.index')->with('status', 'editor-updated');
    }

    public function destroyEditor(User $user): RedirectResponse
    {
        $editor = $this->requireRole($user, User::ROLE_EDITOR);

        SystemLogRecorder::record(
            action: 'editor_deleted',
            actor: request()->user(),
            target: $editor,
            request: request(),
            description: 'Editor deleted by admin.'
        );

        ProfileImageManager::delete($editor->profile_image_path);
        $editor->delete();

        return redirect()->route('admin.editors.index')->with('status', 'editor-deleted');
    }

    public function downloadMemberGovernmentId(User $user)
    {
        $member = $this->requireRole($user, User::ROLE_USER);

        abort_unless($member->government_id_path, 404);
        abort_unless(Storage::exists($member->government_id_path), 404);

        return response()->file(Storage::path($member->government_id_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="member-government-id-'.$member->id.'.pdf"',
        ]);
    }

    private function memberRules(?User $member = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($member?->id)],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique(User::class, 'mobile_number')->ignore($member?->id),
                Rule::unique(User::class, 'primary_phone')->ignore($member?->id),
            ],
            'government_id' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
            'surety_name' => ['required', 'string', 'max:255'],
            'surety_relation' => ['required', Rule::in(self::SURETY_RELATIONS)],
            'surety_phone_number' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'surety_address' => ['required', 'string', 'max:1000'],
            'surety_government_id' => ['required', 'string', 'max:255'],
            'surety_bank_name' => ['required', 'string', 'max:255'],
            'surety_cheque_book_number' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_image' => ['nullable', 'boolean'],
            'password' => [$member ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    private function editorRules(?User $editor = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($editor?->id)],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{7,15}$/',
                Rule::unique(User::class, 'mobile_number')->ignore($editor?->id),
                Rule::unique(User::class, 'primary_phone')->ignore($editor?->id),
            ],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_image' => ['nullable', 'boolean'],
            'password' => [$editor ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    private function requireRole(User $user, string $role): User
    {
        abort_unless($user->role === $role, 404);

        return $user;
    }
}
