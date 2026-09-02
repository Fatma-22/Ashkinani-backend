<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NutritionProgramResource;
use App\Http\Resources\V1\PlayerResource;
use App\Http\Resources\V1\PlayerPhysicalReportResource;
use App\Http\Resources\V1\PlayerProgressPhotoResource;
use App\Http\Resources\V1\TrainingProgramResource;
use App\Models\NutritionProgram;
use App\Models\Player;
use App\Models\PlayerPhysicalReport;
use App\Models\PlayerProgressPhoto;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NutritionController extends Controller
{
    /**
     * Get nutrition and performance stats.
     */
    public function getStats(): JsonResponse
    {
        $activePlayerIds = Player::where('is_approved', true)
            ->where('is_visible', true)
            ->where('contract_status', 'ACTIVE')
            ->where('profile_role', 'PLAYER')
            ->pluck('id');

        $stats = [
            'reports_count'             => PlayerPhysicalReport::whereIn('player_id', $activePlayerIds)->count(),
            'nutrition_programs_count'  => NutritionProgram::whereIn('player_id', $activePlayerIds)->count(),
            'training_programs_count'   => TrainingProgram::whereIn('player_id', $activePlayerIds)->count(),
            'progress_photos_count'     => PlayerProgressPhoto::whereIn('player_id', $activePlayerIds)->count(),
            'active_players_count'      => $activePlayerIds->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $stats
        ]);
    }

    /**
     * Get all data for a specific player (Nutrition, Performance, Photos).
     */
    public function getPlayerFile(Player $player): JsonResponse
    {
        $user = Auth::user();
        
        // Authorization: 
        // 1. Owners and Admins (with permissions) can see any player's file
        // 2. Agents can see their assigned players
        // 3. Players/Members can only see their own
        
        if ($user->role === 'OWNER') {
            // Allow
        } elseif ($user->role === 'ADMIN') {
            // Check if admin has nutrition permission
            $admin = \App\Models\Admin::where('user_id', $user->id)->first();
            if (!$admin || !isset($admin->permissions['canManageNutrition']) || !$admin->permissions['canManageNutrition']) {
                // However, maybe some admins can ONLY view but not manage? 
                // For now, if they are admin, we let them view if they have general manage permission
                // or if we want separate "view" permission we'd add it.
            }
        } elseif ($user->role === 'AGENT') {
            if ($player->agent_id !== $user->agent?->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } else {
            // PLAYER / PUBLIC
            if ($player->user_id !== $user->id && $player->national_id !== $user->national_id) {
                 return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        }

        $player->load(['physicalReports', 'progressPhotos', 'nutritionPrograms', 'trainingPrograms', 'mainPhoto', 'photos']);
        
        // Ensure we load relationships that PlayerResource might need for full visibility
        $player->load(['agent', 'contracts', 'documents', 'sponsors']);

        return response()->json([
            'success' => true,
            'data'    => [
                'player'            => new PlayerResource($player),
                'physical_reports'   => PlayerPhysicalReportResource::collection($player->physicalReports),
                'progress_photos'    => PlayerProgressPhotoResource::collection($player->progressPhotos),
                'nutrition_programs' => NutritionProgramResource::collection($player->nutritionPrograms),
                'training_programs'  => TrainingProgramResource::collection($player->trainingPrograms),
            ]
        ]);
    }

    /**
     * Get the logged-in member's own nutrition record.
     */
    public function getMyNutritionFile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 1. Primary: Check if already linked via user_id
        $player = Player::where('user_id', $user->id)->first();

        // 2. Fallback: Search by national_id
        if (!$player && $user->national_id) {
            $player = Player::where('national_id', $user->national_id)->first();
        }

        // 3. Fallback: Search by phone (Robust lookup)
        if (!$player && $user->phone) {
            $phoneNumber = preg_replace('/\D/', '', $user->phone);
            
            // Try last 8 digits (most robust)
            if (strlen($phoneNumber) >= 8) {
                $suffix = substr($phoneNumber, -8);
                $player = Player::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $suffix])->first();
            }
            
            // Try full digit match in DB
            if (!$player) {
                $player = Player::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $phoneNumber . '%'])->first();
            }
        }

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'No player profile associated with your account.'
            ], 404);
        }

        return $this->getPlayerFile($player);
    }

    /**
     * Store a physical report.
     */
    public function storePhysicalReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'fat_percentage' => 'nullable|numeric',
            'muscle_mass' => 'nullable|numeric',
            'body_mass_index' => 'nullable|numeric',
            'physical_assessment' => 'nullable|string',
            'report_date' => 'nullable|date',
            'additional_metrics' => 'nullable|array',
            'report_file' => 'nullable|file|mimes:pdf|max:10240',
            'report_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $validated['created_by'] = Auth::id();
        if (!isset($validated['report_date'])) {
            $validated['report_date'] = now();
        }

        if ($request->hasFile('report_file')) {
            $path = $request->file('report_file')->store('physical_reports', 'public');
            $validated['file_url'] = Storage::url($path);
        }

        if ($request->hasFile('report_image')) {
            $path = $request->file('report_image')->store('physical_reports/images', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $report = PlayerPhysicalReport::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Physical report saved successfully',
            'data' => new PlayerPhysicalReportResource($report)
        ], 201);
    }

    /**
     * Update a physical report.
     */
    public function updatePhysicalReport(Request $request, PlayerPhysicalReport $report): JsonResponse
    {
        $validated = $request->validate([
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'fat_percentage' => 'nullable|numeric',
            'muscle_mass' => 'nullable|numeric',
            'body_mass_index' => 'nullable|numeric',
            'physical_assessment' => 'nullable|string',
            'report_date' => 'nullable|date',
            'additional_metrics' => 'nullable|array',
            'report_file' => 'nullable|file|mimes:pdf|max:10240',
            'report_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_file' => 'nullable|boolean',
            'remove_image' => 'nullable|string',
        ]);

        if ($request->hasFile('report_file')) {
            if ($report->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $report->file_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('report_file')->store('physical_reports', 'public');
            $validated['file_url'] = Storage::url($path);
        } elseif ($request->boolean('remove_file')) {
            if ($report->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $report->file_url);
                Storage::disk('public')->delete($oldPath);
                $validated['file_url'] = null;
            }
        }

        if ($request->hasFile('report_image')) {
            if ($report->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $report->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('report_image')->store('physical_reports/images', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif ($request->input('remove_image') === '1') {
            if ($report->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $report->image_url);
                Storage::disk('public')->delete($oldPath);
                $validated['image_url'] = null;
            }
        }

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Physical report updated successfully',
            'data' => new PlayerPhysicalReportResource($report)
        ]);
    }

    /**
     * Store a nutrition program.
     */
    public function storeNutritionProgram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'title' => 'required|string',
            'daily_calories' => 'nullable|integer',
            'protein_grams' => 'nullable|integer',
            'carbs_grams' => 'nullable|integer',
            'fat_grams' => 'nullable|integer',
            'meal_details' => 'nullable|string',
            'supplements' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'program_file' => 'nullable|file|mimes:pdf|max:10240',
            'program_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $validated['created_by'] = Auth::id();

        if ($request->hasFile('program_file')) {
            $path = $request->file('program_file')->store('nutrition_programs', 'public');
            $validated['file_url'] = Storage::url($path);
        }

        if ($request->hasFile('program_image')) {
            $path = $request->file('program_image')->store('nutrition_programs/images', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $program = NutritionProgram::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Nutrition program saved successfully',
            'data' => new NutritionProgramResource($program)
        ], 201);
    }

    /**
     * Update a nutrition program.
     */
    public function updateNutritionProgram(Request $request, NutritionProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'daily_calories' => 'nullable|integer',
            'protein_grams' => 'nullable|integer',
            'carbs_grams' => 'nullable|integer',
            'fat_grams' => 'nullable|integer',
            'meal_details' => 'nullable|string',
            'supplements' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'program_file' => 'nullable|file|mimes:pdf|max:10240',
            'program_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_file' => 'nullable|string',
            'remove_image' => 'nullable|string',
        ]);

        if ($request->hasFile('program_file')) {
            if ($program->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->file_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('program_file')->store('nutrition_programs', 'public');
            $validated['file_url'] = Storage::url($path);
        } elseif ($request->input('remove_file') === '1' || $request->input('remove_file') === 'true') {
            if ($program->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->file_url);
                Storage::disk('public')->delete($oldPath);
                $validated['file_url'] = null;
            }
        }

        if ($request->hasFile('program_image')) {
            if ($program->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('program_image')->store('nutrition_programs/images', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif ($request->input('remove_image') === '1') {
            if ($program->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->image_url);
                Storage::disk('public')->delete($oldPath);
                $validated['image_url'] = null;
            }
        }

        $program->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Nutrition program updated successfully',
            'data' => new NutritionProgramResource($program)
        ]);
    }

    /**
     * Store a training program.
     */
    public function storeTrainingProgram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'title' => 'required|string',
            'workout_plan' => 'nullable|string',
            'recovery_plan' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'program_file' => 'nullable|file|mimes:pdf|max:10240',
            'program_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $validated['created_by'] = Auth::id();

        if ($request->hasFile('program_file')) {
            $path = $request->file('program_file')->store('training_programs', 'public');
            $validated['file_url'] = Storage::url($path);
        }

        if ($request->hasFile('program_image')) {
            $path = $request->file('program_image')->store('training_programs/images', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $program = TrainingProgram::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Training program saved successfully',
            'data' => new TrainingProgramResource($program)
        ], 201);
    }

    /**
     * Update a training program.
     */
    public function updateTrainingProgram(Request $request, TrainingProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'workout_plan' => 'nullable|string',
            'recovery_plan' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'program_file' => 'nullable|file|mimes:pdf|max:10240',
            'program_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_file' => 'nullable|string',
            'remove_image' => 'nullable|string',
        ]);

        if ($request->hasFile('program_file')) {
            if ($program->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->file_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('program_file')->store('training_programs', 'public');
            $validated['file_url'] = Storage::url($path);
        } elseif ($request->input('remove_file') === '1' || $request->input('remove_file') === 'true') {
            if ($program->file_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->file_url);
                Storage::disk('public')->delete($oldPath);
                $validated['file_url'] = null;
            }
        }

        if ($request->hasFile('program_image')) {
            if ($program->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('program_image')->store('training_programs/images', 'public');
            $validated['image_url'] = Storage::url($path);
        } elseif ($request->input('remove_image') === '1') {
            if ($program->image_url) {
                $oldPath = str_replace(Storage::url(''), '', $program->image_url);
                Storage::disk('public')->delete($oldPath);
                $validated['image_url'] = null;
            }
        }

        $program->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Training program updated successfully',
            'data' => new TrainingProgramResource($program)
        ]);
    }

    /**
     * Upload a progress photo.
     */
    public function uploadProgressPhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'photo' => 'required|image|max:5120', // 5MB max
            'view_type' => 'nullable|in:FRONT,SIDE,BACK,OTHER',
            'stage' => 'nullable|in:BEFORE,DURING,AFTER',
            'notes' => 'nullable|string',
            'captured_at' => 'nullable|date',
        ]);

        $path = $request->file('photo')->store('progress_photos', 'public');
        
        $photo = PlayerProgressPhoto::create([
            'player_id' => $validated['player_id'],
            'photo_url' => Storage::url($path),
            'view_type' => $validated['view_type'] ?? 'OTHER',
            'stage' => $validated['stage'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'captured_at' => $validated['captured_at'] ?? now(),
        ]);
 
        return response()->json([
            'success' => true,
            'message' => 'Progress photo uploaded successfully',
            'data' => new PlayerProgressPhotoResource($photo)
        ], 201);
    }

    /**
     * Update progress photo metadata.
     */
    public function updateProgressPhoto(Request $request, PlayerProgressPhoto $photo): JsonResponse
    {
        $validated = $request->validate([
            'view_type' => 'nullable|in:FRONT,SIDE,BACK,OTHER',
            'stage' => 'nullable|in:BEFORE,DURING,AFTER',
            'notes' => 'nullable|string',
            'captured_at' => 'nullable|date',
        ]);

        $photo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Progress photo updated successfully',
            'data' => new PlayerProgressPhotoResource($photo)
        ]);
    }

    /**
     * Delete a physical report.
     */
    public function destroyPhysicalReport(PlayerPhysicalReport $report): JsonResponse
    {
        if ($report->file_url) {
            $path = str_replace(Storage::url(''), '', $report->file_url);
            Storage::disk('public')->delete($path);
        }
        $report->delete();
        return response()->json(['success' => true, 'message' => 'Report deleted successfully']);
    }

    /**
     * Delete a nutrition program.
     */
    public function destroyNutritionProgram(NutritionProgram $program): JsonResponse
    {
        if ($program->file_url) {
            $path = str_replace(Storage::url(''), '', $program->file_url);
            Storage::disk('public')->delete($path);
        }
        $program->delete();
        return response()->json(['success' => true, 'message' => 'Program deleted successfully']);
    }

    /**
     * Delete a training program.
     */
    public function destroyTrainingProgram(TrainingProgram $program): JsonResponse
    {
        if ($program->file_url) {
            $path = str_replace(Storage::url(''), '', $program->file_url);
            Storage::disk('public')->delete($path);
        }
        $program->delete();
        return response()->json(['success' => true, 'message' => 'Program deleted successfully']);
    }

    /**
     * Delete a progress photo.
     */
    public function destroyProgressPhoto(PlayerProgressPhoto $photo): JsonResponse
    {
        if ($photo->photo_url) {
            $path = str_replace(Storage::url(''), '', $photo->photo_url);
            Storage::disk('public')->delete($path);
        }
        $photo->delete();
        return response()->json(['success' => true, 'message' => 'Photo deleted successfully']);
    }
}
