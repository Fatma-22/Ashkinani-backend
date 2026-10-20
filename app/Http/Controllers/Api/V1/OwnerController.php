<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\Deal;
use App\Models\FinancialRecord;
use App\Models\Player;
use App\Services\ArabicSearchService;
use App\Services\FinancialSyncService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    protected $mediaService;
    protected $financialSync;
    protected $arabicSearch;

    public function __construct(
        MediaService $mediaService,
        FinancialSyncService $financialSync,
        ArabicSearchService $arabicSearch
    ) {
        $this->mediaService = $mediaService;
        $this->financialSync = $financialSync;
        $this->arabicSearch = $arabicSearch;
    }

    public function getFinancialStats(Request $request)
    {
        $incomeQuery = FinancialRecord::where('type', 'INCOME');
        $expenseQuery = FinancialRecord::where('type', 'EXPENSE');
        $arrearsQuery = FinancialRecord::where('type', 'ARREARS');

        if ($request->has('start_date') && $request->start_date) {
            $incomeQuery->where('transaction_date', '>=', $request->start_date);
            $expenseQuery->where('transaction_date', '>=', $request->start_date);
            $arrearsQuery->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $incomeQuery->where('transaction_date', '<=', $request->end_date);
            $expenseQuery->where('transaction_date', '<=', $request->end_date);
            $arrearsQuery->where('transaction_date', '<=', $request->end_date);
        }

        $totalIncome = (float) $incomeQuery->sum('amount');
        $totalExpense = (float) $expenseQuery->sum('amount');
        $totalArrears = (float) $arrearsQuery->sum('amount');

        $monthlyIncome = FinancialRecord::where('type', 'INCOME')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('amount');

        $monthlyExpense = FinancialRecord::where('type', 'EXPENSE')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('amount');

        $monthlyArrears = FinancialRecord::where('type', 'ARREARS')
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year)
            ->sum('amount');

        return $this->success([
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalArrears' => $totalArrears,
            'netProfit' => $totalIncome - $totalExpense,
            'monthlyIncome' => (float) $monthlyIncome,
            'monthlyExpense' => (float) $monthlyExpense,
            'monthlyArrears' => (float) $monthlyArrears,
            'yearlyIncome' => (float) FinancialRecord::where('type', 'INCOME')->whereYear('transaction_date', Carbon::now()->year)->sum('amount'),
            'yearlyExpense' => (float) FinancialRecord::where('type', 'EXPENSE')->whereYear('transaction_date', Carbon::now()->year)->sum('amount'),
            'yearlyArrears' => (float) FinancialRecord::where('type', 'ARREARS')->whereYear('transaction_date', Carbon::now()->year)->sum('amount'),
            'totalDeals' => Deal::count(),
            'totalDealsAmount' => Deal::sum('amount'),
        ]);
    }

    public function getAdmins()
    {
        $admins = Admin::with('user')
            ->withCount('scoutedPlayers')
            ->get()
            ->map(function ($admin) {
                // Ensure permissions is always a proper array
                $perms = $admin->permissions;
                if (is_string($perms)) {
                    $perms = json_decode($perms, true) ?? [];
                }
                if (!is_array($perms)) {
                    $perms = [];
                }

                // Make sure all keys exist
                $defaultPerms = [
                    'canAddPlayers' => false,
                    'canEditPlayers' => false,
                    'canDeletePlayers' => false,
                    'canAddAgents' => false,
                    'canEditAgents' => false,
                    'canDeleteAgents' => false,
                    'canViewReports' => false,
                    'canViewFinancials' => false,
                    'canAddDeals' => false,
                    'canEditDeals' => false,
                    'canDeleteDeals' => false,
                    'canManageNews' => false,
                    'canManageLanding' => false,
                    'canManageCVRequests' => false,
                    'canManageMeetings' => false,
                    'canManageMembers' => false,
                    'canManageSponsors' => false,
                    'canManageNutrition' => false,
                    'canManageFederations' => false,
                    'canManageClubs' => false,
                    'canManageScouts' => false,
                ];

                $admin->permissions = array_merge($defaultPerms, $perms);

                return $admin;
            });

        return $this->success($admins);
    }

    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|string',
            'password' => 'nullable',
            'phone' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_scout' => 'nullable|boolean',
            'canManageScouts' => 'nullable|boolean',
        ]);

        if ($request->filled('phone') && str_contains($validated['phone'], '@')) {
            return $this->error('The phone number field cannot contain an email address.', 422);
        }

        return DB::transaction(function () use ($validated) {
            $defaultPermissions = [
                'canAddPlayers' => false,
                'canEditPlayers' => false,
                'canDeletePlayers' => false,
                'canAddAgents' => false,
                'canEditAgents' => false,
                'canDeleteAgents' => false,
                'canViewReports' => false,
                'canViewFinancials' => false,
                'canAddDeals' => false,
                'canEditDeals' => false,
                'canDeleteDeals' => false,
                'canManageNews' => false,
                'canManageLanding' => false,
                'canManageCVRequests' => false,
                'canManageMeetings' => false,
                'canManageMembers' => false,
                'canManageSponsors' => false,
                'canManageNutrition' => false,
                'canManageFederations' => false,
                'canManageClubs' => false,
                'canManageScouts' => false,
            ];

            $isActive = $validated['is_active'] ?? true;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'ADMIN',
                'is_active' => $isActive,
                'phone' => $validated['phone'] ?? null,
            ]);

            $admin = Admin::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'permissions' => array_merge($defaultPermissions, $validated['permissions'] ?? []),
                'is_active' => $isActive,
                'is_scout' => $validated['is_scout'] ?? false,
                'created_by' => auth()->id(),
            ]);

            // Ensure permissions is properly formatted
            if (!is_array($admin->permissions)) {
                $admin->permissions = [];
            }

            return $this->success($admin->load('user'), 'Admin created successfully', 201);
        });
    }

    public function updateAdmin(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|string',
            'password' => 'nullable',
            'phone' => 'nullable|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_scout' => 'nullable|boolean',
            'canManageScouts' => 'nullable|boolean',
        ]);

        // Basic check to prevent saving email into phone field (likely browser autofill error)
        if ($request->filled('phone') && str_contains($validated['phone'], '@')) {
            return $this->error('The phone number field cannot contain an email address.', 422);
        }

        return DB::transaction(function () use ($validated, $admin, $request) {
            $defaultPermissions = [
                'canAddPlayers' => false,
                'canEditPlayers' => false,
                'canDeletePlayers' => false,
                'canAddAgents' => false,
                'canEditAgents' => false,
                'canDeleteAgents' => false,
                'canViewReports' => false,
                'canViewFinancials' => false,
                'canAddDeals' => false,
                'canEditDeals' => false,
                'canDeleteDeals' => false,
                'canManageNews' => false,
                'canManageLanding' => false,
                'canManageCVRequests' => false,
                'canManageMeetings' => false,
                'canManageMembers' => false,
                'canManageSponsors' => false,
                'canManageNutrition' => false,
                'canManageFederations' => false,
                'canManageClubs' => false,
                'canManageScouts' => false,
            ];

            $userData = [];

            if (isset($validated['name'])) {
                $userData['name'] = $validated['name'];
            }

            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'];
            }

            if (isset($validated['phone'])) {
                $userData['phone'] = $validated['phone'];
            }

            if ($request->filled('password')) {
                $userData['password'] = bcrypt($validated['password']);
            }

            if (isset($validated['is_active'])) {
                $userData['is_active'] = $validated['is_active'];

                // If deactivating, revoke all tokens
                if (!$validated['is_active']) {
                    $admin->user->tokens()->delete();
                }
            }

            // Update user if there's any user data to update
            if (!empty($userData)) {
                $admin->user->update($userData);
            }

            // Prepare admin update data
            $adminUpdateData = [];

            if (isset($validated['name'])) {
                $adminUpdateData['name'] = $validated['name'];
            }

            if (isset($validated['email'])) {
                $adminUpdateData['email'] = $validated['email'];
            }

            if (isset($validated['phone'])) {
                $adminUpdateData['phone'] = $validated['phone'];
            }

            if (isset($validated['permissions'])) {
                $adminUpdateData['permissions'] = array_merge($defaultPermissions, $validated['permissions']);
            }

            if (isset($validated['is_active'])) {
                $adminUpdateData['is_active'] = $validated['is_active'];
            }

            if (isset($validated['is_scout'])) {
                $adminUpdateData['is_scout'] = $validated['is_scout'];
            }

            // Update admin if there's any data to update
            if (!empty($adminUpdateData)) {
                $admin->update($adminUpdateData);
            }

            // Refresh the admin instance to get updated values
            $admin->refresh();

            // Ensure permissions is properly formatted
            if (!is_array($admin->permissions)) {
                $admin->permissions = [];
            }

            return $this->success($admin->load('user'), 'Admin updated successfully');
        });
    }

    public function destroyAdmin(Admin $admin)
    {
        return DB::transaction(function () use ($admin) {
            $admin->user->delete();
            $admin->delete();
            return $this->success(null, 'Admin deleted successfully');
        });
    }

    public function getEmployees()
    {
        $employees = Employee::all()->map(function ($employee) {
            if ($employee->contract_file) {
                $employee->contract_file_url = $this->mediaService->getFullUrl($employee->contract_file);
            }
            return $employee;
        });
        return $this->success($employees);
    }

    public function getFinancialRecords(Request $request)
    {
        $query = FinancialRecord::query()
            ->with('related')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('type')) {
            $query->where('type', strtoupper((string) $request->input('type')));
        }

        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->input('end_date'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper((string) $request->input('currency')));
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', (float) $request->input('min_amount'));
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', (float) $request->input('max_amount'));
        }

        if ($request->filled('related_id')) {
            $query->where('related_id', $request->input('related_id'));
        }

        if ($request->filled('related_type')) {
            $class = $this->financialSync->modelForType((string) $request->input('related_type'));

            if ($class) {
                $query->where('related_type', $class);
            }
        }

        if ($request->filled('payment_linked')) {
            if ($request->boolean('payment_linked')) {
                $query->whereNotNull('player_payment_id');
            } else {
                $query->whereNull('player_payment_id');
            }
        }

        $search = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));

        if ($search !== '') {
            $this->applyFinancialSearch($query, $search);
        }

        $perPage = (int) $request->input('per_page', 0);

        if ($perPage > 0) {
            $query->limit(min($perPage, 10000));
        }

        return $this->success($query->get());
    }

    /**
     * Professional cross-field search for the financial ledger.
     */
    private function applyFinancialSearch($query, string $search): void
    {
        $like = '%' . $search . '%';

        $query->where(function ($q) use ($like, $search) {
            $q->where('description', 'like', $like)
                ->orWhere('description_ar', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('category_ar', 'like', $like)
                ->orWhere('related_to', 'like', $like)
                ->orWhere('invoice_path', 'like', $like);

            if (is_numeric($search)) {
                $q->orWhere('amount', (float) $search);
            }

            if (preg_match('/[\x{0600}-\x{06FF}]/u', $search)) {
                $pattern = $this->arabicSearch->generateRegexPattern($search);

                if ($pattern !== '') {
                    $q->orWhereRaw('description_ar REGEXP ?', [$pattern])
                        ->orWhereRaw('related_to REGEXP ?', [$pattern]);
                }
            }

            $q->orWhereHasMorph(
                'related',
                [Player::class, Employee::class, User::class, Agent::class, Admin::class],
                function ($m, $type) use ($like) {
                    $m->where(function ($sub) use ($like, $type) {
                        $sub->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like);

                        if (in_array($type, [Player::class, Employee::class, Agent::class], true)) {
                            $sub->orWhere('name_ar', 'like', $like);
                        }

                        if ($type === Player::class) {
                            $sub->orWhere('national_id', 'like', $like);
                        }
                    });
                }
            );
        });
    }

    /**
     * Search every person registered on the website (athletes, coaches,
     * employees, agents, admins and members) to link financial records.
     */
    public function searchPersons(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $type = strtolower((string) $request->input('type', 'all'));
        $limit = max(5, min((int) $request->input('limit', 25), 50));
        $results = [];

        if (in_array($type, ['all', 'player', 'coach'], true)) {
            $players = Player::query()
                ->with('club:id,name,name_ar,logo_path')
                ->when($search !== '', fn ($q) => $this->applyPersonSearch($q, $search, true))
                ->when($type === 'coach', fn ($q) => $q->where('profile_role', 'COACH'))
                ->orderBy('name')
                ->limit($limit)
                ->get();

            foreach ($players as $player) {
                $results[] = [
                    'key' => 'player:' . $player->id,
                    'type' => 'player',
                    'id' => $player->id,
                    'name' => $player->name,
                    'name_ar' => $player->name_ar,
                    'role' => $player->profile_role ?: 'PLAYER',
                    'email' => $player->email,
                    'phone' => $player->phone,
                    'subtitle' => trim(implode(' • ', array_filter([
                        $player->sport,
                        $player->club->name ?? null,
                    ]))),
                ];
            }
        }

        if ($type === 'all' || $type === 'employee') {
            $employees = Employee::query()
                ->when($search !== '', fn ($q) => $this->applyPersonSearch($q, $search, true))
                ->orderBy('name')
                ->limit($limit)
                ->get();

            foreach ($employees as $employee) {
                $results[] = [
                    'key' => 'employee:' . $employee->id,
                    'type' => 'employee',
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'name_ar' => $employee->name_ar,
                    'role' => 'EMPLOYEE',
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'subtitle' => trim(implode(' • ', array_filter([
                        $employee->position,
                        $employee->department,
                    ]))),
                ];
            }
        }

        if ($type === 'all' || $type === 'agent') {
            $agents = Agent::query()
                ->when($search !== '', fn ($q) => $this->applyPersonSearch($q, $search, true))
                ->orderBy('name')
                ->limit($limit)
                ->get();

            foreach ($agents as $agent) {
                $results[] = [
                    'key' => 'agent:' . $agent->id,
                    'type' => 'agent',
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'name_ar' => $agent->name_ar,
                    'role' => 'AGENT',
                    'email' => $agent->email,
                    'phone' => $agent->phone,
                    'subtitle' => $agent->company,
                ];
            }
        }

        if ($type === 'all' || $type === 'admin') {
            $admins = Admin::query()
                ->when($search !== '', fn ($q) => $this->applyPersonSearch($q, $search, false))
                ->orderBy('name')
                ->limit($limit)
                ->get();

            foreach ($admins as $admin) {
                $results[] = [
                    'key' => 'admin:' . $admin->id,
                    'type' => 'admin',
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'name_ar' => null,
                    'role' => 'ADMIN',
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'subtitle' => $admin->email,
                ];
            }
        }

        if ($type === 'all' || $type === 'member') {
            $members = User::query()
                ->where('role', 'PUBLIC')
                ->whereDoesntHave('player')
                ->whereDoesntHave('agent')
                ->whereDoesntHave('adminProfile')
                ->when($search !== '', fn ($q) => $this->applyPersonSearch($q, $search, false))
                ->orderBy('name')
                ->limit($limit)
                ->get();

            foreach ($members as $member) {
                $results[] = [
                    'key' => 'member:' . $member->id,
                    'type' => 'member',
                    'id' => $member->id,
                    'name' => $member->name,
                    'name_ar' => null,
                    'role' => 'MEMBER',
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'subtitle' => $member->organization ?: $member->email,
                ];
            }
        }

        usort($results, function ($a, $b) {
            return strcasecmp(
                (string) ($a['name'] ?: $a['name_ar']),
                (string) ($b['name'] ?: $b['name_ar'])
            );
        });

        return $this->success(array_slice($results, 0, $limit));
    }

    private function applyPersonSearch($query, string $search, bool $withArabicAndNational = false)
    {
        $like = '%' . $search . '%';

        return $query->where(function ($q) use ($search, $like, $withArabicAndNational) {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);

            if ($withArabicAndNational) {
                $q->orWhere('name_ar', 'like', $like)
                    ->orWhere('national_id', 'like', $like);
            }

            if (preg_match('/[\x{0600}-\x{06FF}]/u', $search)) {
                $pattern = $this->arabicSearch->generateRegexPattern($search);

                if ($pattern !== '') {
                    $q->orWhereRaw('name REGEXP ?', [$pattern]);

                    if ($withArabicAndNational) {
                        $q->orWhereRaw('name_ar REGEXP ?', [$pattern]);
                    }
                }
            }
        });
    }

    public function storeFinancialRecord(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'category' => 'nullable|string',
            'category_ar' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'related_to' => 'nullable|string',
            'related_type' => 'nullable|string|in:player,employee,member,agent,admin',
            'related_id' => 'nullable|integer',
            'sync_payment' => 'nullable|boolean',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $syncPayment = $request->boolean('sync_payment', true);

        unset($validated['invoice'], $validated['sync_payment']);

        if ($request->hasFile('invoice')) {
            $validated['invoice_path'] = $this->mediaService->upload($request->file('invoice'), 'financial-invoices');
        }

        $person = $this->resolveRelatedPerson($validated);

        $record = FinancialRecord::create($validated + ['created_by' => auth()->id()]);

        // A player-linked record automatically creates/updates his payment.
        if ($person && $syncPayment) {
            $this->financialSync->syncRecordToPayment($record);
        }

        return $this->success($record->fresh('related'), 'Financial record created successfully', 201);
    }

    public function updateFinancialRecord(Request $request, FinancialRecord $record)
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'category' => 'nullable|string',
            'category_ar' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'related_to' => 'nullable|string',
            'related_type' => 'nullable|string|in:player,employee,member,agent,admin',
            'related_id' => 'nullable|integer',
            'sync_payment' => 'nullable|boolean',
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remove_invoice' => 'nullable|boolean',
        ]);

        $syncPayment = $request->boolean('sync_payment', true);
        $relatedProvided = $request->has('related_type') || $request->has('related_id');

        unset($validated['invoice'], $validated['remove_invoice'], $validated['sync_payment']);

        if ($request->hasFile('invoice')) {
            $this->mediaService->delete($record->invoice_path);
            $validated['invoice_path'] = $this->mediaService->upload($request->file('invoice'), 'financial-invoices');
        } elseif ($request->boolean('remove_invoice')) {
            $this->mediaService->delete($record->invoice_path);
            $validated['invoice_path'] = null;
        }

        if ($relatedProvided) {
            $person = $this->resolveRelatedPerson($validated, false);

            if (!$person) {
                // Explicit unlink from the person.
                $validated['related_type'] = null;
                $validated['related_id'] = null;
            }
        }

        $record->update($validated);
        $record->refresh();

        if ($syncPayment && $record->payment_component === FinancialSyncService::COMPONENT_REMAINING) {
            $this->financialSync->applyRemainingRecordToPayment($record);
            $record->refresh();
        }

        $isPlayerLinked = $this->financialSync->shortType($record->related_type) === 'player'
            && $record->related_id;

        if ($syncPayment && $isPlayerLinked) {
            $this->financialSync->syncRecordToPayment($record);
        } elseif ($syncPayment && $record->player_payment_id) {
            // The record moved to another (non-player) person: drop the payment mirror.
            $this->financialSync->detachRecordFromPayment($record);
        }

        return $this->success($record->fresh('related'), 'Financial record updated successfully');
    }

    public function destroyFinancialRecord(FinancialRecord $record)
    {
        // The outstanding row is derived from the payment: deleting it waives the remainder.
        if ($record->payment_component === FinancialSyncService::COMPONENT_REMAINING) {
            $this->financialSync->removeRemainingRecord($record);

            return $this->success(null, 'Financial record deleted successfully');
        }

        $this->mediaService->delete($record->invoice_path);

        // Deleting a payment-generated ledger row also removes its payment.
        if (!$this->financialSync->deleteForRecord($record)) {
            $record->delete();
        }

        return $this->success(null, 'Financial record deleted successfully');
    }

    private function resolveRelatedPerson(array &$validated, bool $unsetWhenMissing = true): ?\Illuminate\Database\Eloquent\Model
    {
        $type = $validated['related_type'] ?? null;
        $id = $validated['related_id'] ?? null;

        if (!$type || !$id) {
            if ($unsetWhenMissing) {
                unset($validated['related_type'], $validated['related_id']);
            }

            return null;
        }

        $person = $this->financialSync->resolvePerson($type, $id);

        if (!$person) {
            abort(response()->json([
                'success' => false,
                'message' => 'Related person not found',
            ], 404));
        }

        $validated['related_type'] = get_class($person);
        $validated['related_id'] = $person->id;

        if (empty($validated['related_to'])) {
            $validated['related_to'] = $this->financialSync->personName($person);
        }

        return $person;
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'name_ar' => 'nullable|string',
            'position' => 'nullable|string',
            'position_ar' => 'nullable|string',
            'department' => 'nullable|string',
            'department_ar' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'hire_date' => 'nullable|date',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'national_id' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'year_of_birth' => 'nullable|integer',
            'nationality' => 'nullable|string',
            'nationality_ar' => 'nullable|string',
            'currency' => 'nullable|string|max:10',
        ]);

        if ($request->hasFile('contract_file')) {
            $validated['contract_file'] = $this->mediaService->upload($request->file('contract_file'), 'employees/contracts');
        }

        $employee = Employee::create($validated);
        return $this->success($employee, 'Employee created successfully', 201);
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'name_ar' => 'nullable|string',
            'position' => 'nullable|string',
            'position_ar' => 'nullable|string',
            'department' => 'nullable|string',
            'department_ar' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'hire_date' => 'nullable|date',
            'phone' => 'nullable|string',
            'email' => 'nullable|string',
            'national_id' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'year_of_birth' => 'nullable|integer',
            'nationality' => 'nullable|string',
            'nationality_ar' => 'nullable|string',
            'currency' => 'nullable|string|max:10',
        ]);

        if ($request->hasFile('contract_file')) {
            // Delete old file if exists
            if ($employee->contract_file) {
                $this->mediaService->delete($employee->contract_file);
            }
            $validated['contract_file'] = $this->mediaService->upload($request->file('contract_file'), 'employees/contracts');
        }

        $employee->update($validated);
        return $this->success($employee, 'Employee updated successfully');
    }

    public function destroyEmployee(Employee $employee)
    {
        $employee->delete();
        return $this->success(null, 'Employee deleted successfully');
    }
}
