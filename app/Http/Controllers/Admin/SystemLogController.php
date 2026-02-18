<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $action = trim((string) $request->query('action', 'all'));
        $role = trim((string) $request->query('role', 'all'));

        $allowedActions = array_merge(['all'], SystemLog::actionNames());

        if (! in_array($action, $allowedActions, true)) {
            $action = 'all';
        }

        if (! in_array($role, ['all', User::ROLE_ADMIN, User::ROLE_EDITOR], true)) {
            $role = 'all';
        }

        $query = SystemLog::query()
            ->with([
                'actor:id,name,email,mobile_number,role',
                'targetUser:id,name,email,mobile_number,role',
            ]);

        if ($search !== '') {
            $searchTerm = '%'.$search.'%';
            $query->where(function ($builder) use ($search, $searchTerm): void {
                $builder
                    ->whereHas('actor', function ($actorQuery) use ($searchTerm): void {
                        $actorQuery
                            ->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm)
                            ->orWhere('mobile_number', 'like', $searchTerm);
                    })
                    ->orWhereHas('targetUser', function ($targetQuery) use ($searchTerm): void {
                        $targetQuery
                            ->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm)
                            ->orWhere('mobile_number', 'like', $searchTerm);
                    });
                if (filter_var($search, FILTER_VALIDATE_IP)) {
                    $packedIp = @inet_pton($search);

                    if ($packedIp !== false) {
                        $builder->orWhere('ip_address', $packedIp);
                    }
                }
            });
        }

        if ($action !== 'all') {
            $query->where('action_code', SystemLog::actionCode($action));
        }

        if ($role !== 'all') {
            $query->where('actor_role_code', SystemLog::roleCode($role));
        }

        $logs = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.system-logs.index', [
            'logs' => $logs,
            'actionLabels' => SystemLog::actionLabelsByName(),
            'filters' => [
                'search' => $search,
                'action' => $action,
                'role' => $role,
            ],
        ]);
    }
}
