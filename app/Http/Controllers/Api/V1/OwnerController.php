<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Admin;
use App\Models\Deal;
use App\Models\FinancialRecord;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
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
        $query = FinancialRecord::orderBy('transaction_date', 'desc');

        if ($request->has('type') && $request->type) {
            $query->where('type', strtoupper($request->type));
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        return $this->success($query->get());
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
        ]);

        $record = FinancialRecord::create($validated + ['created_by' => auth()->id()]);
        return $this->success($record, 'Financial record created successfully', 201);
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
        ]);

        $record->update($validated);
        return $this->success($record, 'Financial record updated successfully');
    }

    public function destroyFinancialRecord(FinancialRecord $record)
    {
        $record->delete();
        return $this->success(null, 'Financial record deleted successfully');
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
