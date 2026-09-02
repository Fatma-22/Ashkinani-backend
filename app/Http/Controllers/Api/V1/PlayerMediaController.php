<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadDocumentRequest;
use App\Http\Requests\Media\UploadPhotoRequest;
use App\Http\Resources\V1\PlayerDocumentResource;
use App\Http\Resources\V1\PlayerPhotoResource;
use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\PlayerPhoto;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;

class PlayerMediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function publicUploadPhoto(UploadPhotoRequest $request, Player $player): JsonResponse
    {
        if ($player->is_approved) {
            return $this->error('Cannot modify approved players', 403);
        }
        return $this->uploadPhoto($request, $player);
    }

    public function publicUploadCV(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        if ($player->is_approved) {
            return $this->error('Cannot modify approved players', 403);
        }
        return $this->uploadCV($request, $player);
    }

    /**
     * Add a photo to a player
     */
    public function uploadPhoto(UploadPhotoRequest $request, Player $player): JsonResponse
    {
        if ($request->is_main) {
            $player->photos()->update(['is_main' => false]);
        }

        $path = $this->mediaService->upload($request->file('photo'), 'players/photos');

        $photo = $player->photos()->create([
            'url' => $path,
            'caption' => $request->caption,
            'is_main' => $request->is_main ?? false,
        ]);

        return $this->success(new PlayerPhotoResource($photo), 'Photo uploaded successfully', 201);
    }

    /**
     * Set a photo as main
     */
    public function setMainPhoto(Player $player, PlayerPhoto $photo): JsonResponse
    {
        if ($photo->player_id !== $player->id) {
            return $this->error('Photo does not belong to this player', 403);
        }

        $player->photos()->update(['is_main' => false]);
        $photo->update(['is_main' => true]);

        return $this->success(new PlayerPhotoResource($photo), 'Main photo updated');
    }

    /**
     * Delete a player photo
     */
    public function deletePhoto(Player $player, PlayerPhoto $photo): JsonResponse
    {
        if ($photo->player_id !== $player->id) {
            return $this->error('Photo does not belong to this player', 403);
        }

        $this->mediaService->delete($photo->url);
        $photo->delete();

        return $this->success(null, 'Photo deleted successfully');
    }

    /**
     * Add a document to a player
     */
    public function uploadDocument(UploadDocumentRequest $request, Player $player): JsonResponse
    {
        $path = $this->mediaService->upload($request->file('document'), 'players/documents');

        $document = $player->documents()->create([
            'name' => $request->name,
            'url' => $path,
            'type' => $request->type ?? $request->file('document')->getClientOriginalExtension(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'uploaded_at' => now(),
        ]);

        return $this->success(new PlayerDocumentResource($document), 'Document uploaded successfully', 201);
    }

    /**
     * Update a player document
     */
    public function updateDocument(\Illuminate\Http\Request $request, Player $player, PlayerDocument $document): JsonResponse
    {
        if ($document->player_id !== $player->id) {
            return $this->error('Document does not belong to this player', 403);
        }

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $document->update($request->only(['name', 'start_date', 'end_date']));

        return $this->success(new PlayerDocumentResource($document), 'Document updated successfully');
    }

    /**
     * Delete a player document
     */
    public function deleteDocument(Player $player, PlayerDocument $document): JsonResponse
    {
        if ($document->player_id !== $player->id) {
            return $this->error('Document does not belong to this player', 403);
        }

        $this->mediaService->delete($document->url);
        $document->delete();

        return $this->success(null, 'Document deleted successfully');
    }

    /**
     * Upload a CV for a coach
     */
    public function uploadCV(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf', 'max:51200'], // 50MB max
        ]);

        $allowedRoles = ['COACH', 'PLAYER', 'ADMINISTRATOR', 'REFEREE', 'PHOTOGRAPHER'];
        if (!in_array($player->profile_role, $allowedRoles)) {
            return $this->error('CV cannot be uploaded for this role', 403);
        }

        // Delete old CV if exists
        if ($player->cv_url) {
            $this->mediaService->delete($player->cv_url);
        }

        $path = $this->mediaService->upload($request->file('cv'), 'players/cvs');
        $player->update(['cv_url' => $path]);

        return $this->success(['cv_url' => $this->mediaService->getFullUrl($path)], 'CV uploaded successfully');
    }

    /**
     * Delete a coach CV
     */
    public function deleteCV(Player $player): JsonResponse
    {
        if ($player->cv_url) {
            $this->mediaService->delete($player->cv_url);
            $player->update(['cv_url' => null]);
        }

        return $this->success(null, 'CV deleted successfully');
    }
    /**
     * Upload a Club Logo for a player
     */
    public function uploadClubLogo(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'], // 5MB max
        ]);

        if ($player->club_logo) {
            $this->mediaService->delete($player->club_logo);
        }

        $path = $this->mediaService->upload($request->file('logo'), 'players/club_logos');
        $player->update(['club_logo' => $path]);

        return $this->success(['club_logo' => $this->mediaService->getFullUrl($path)], 'Club logo uploaded successfully');
    }

    /**
     * Delete a Club Logo
     */
    public function deleteClubLogo(Player $player): JsonResponse
    {
        if ($player->club_logo) {
            $this->mediaService->delete($player->club_logo);
            $player->update(['club_logo' => null]);
        }

        return $this->success(null, 'Club logo deleted successfully');
    }

    /**
     * Upload a file for a specific club contract
     */
    public function uploadClubContractFile(\Illuminate\Http\Request $request, Player $player, \App\Models\ClubContract $clubContract): JsonResponse
    {
        if ($clubContract->player_id !== $player->id) {
            return $this->error('Contract does not belong to this player', 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB max
        ]);

        // Delete old file if exists
        if ($clubContract->file_url) {
            $this->mediaService->delete($clubContract->file_url);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/club_contracts');
        $clubContract->update(['file_url' => $path]);

        return $this->success(['file_url' => asset('storage/' . $path)], 'Contract file uploaded successfully');
    }

    /**
     * Upload a file for a specific coach certificate
     */
    public function uploadCertificateFile(\Illuminate\Http\Request $request, Player $player, \App\Models\CoachCertificate $certificate): JsonResponse
    {
        if ($certificate->coach_id !== $player->id) {
            return $this->error('Certificate does not belong to this coach', 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], // 10MB max
        ]);

        // Delete old file if exists
        if ($certificate->certificate_file) {
            $this->mediaService->delete($certificate->certificate_file);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/certificates');
        $certificate->update(['certificate_file' => $path]);

        return $this->success(['file_url' => asset('storage/' . $path)], 'Certificate file uploaded successfully');
    }

    /**
     * Upload volleyball stats PDF for admin's player
     */
    public function uploadVolleyballStatsPdf(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
        ]);

        // Delete old file if exists
        if ($player->volleyball_stats_pdf) {
            $this->mediaService->delete($player->volleyball_stats_pdf);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/volleyball');
        $player->update(['volleyball_stats_pdf' => $path]);

        return $this->success(['volleyball_stats_pdf' => asset('storage/' . $path)], 'Volleyball stats PDF uploaded successfully');
    }

    /**
     * Upload volleyball ranking image for admin's player
     */
    public function uploadVolleyballRankingImage(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max
        ]);

        // Delete old file if exists
        if ($player->volleyball_ranking_image) {
            $this->mediaService->delete($player->volleyball_ranking_image);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/volleyball');
        $player->update(['volleyball_ranking_image' => $path]);

        return $this->success(['volleyball_ranking_image' => asset('storage/' . $path)], 'Volleyball ranking image uploaded successfully');
    }

    /**
     * Delete volleyball stats PDF
     */
    public function deleteVolleyballStatsPdf(Player $player): JsonResponse
    {
        if ($player->volleyball_stats_pdf) {
            $this->mediaService->delete($player->volleyball_stats_pdf);
            $player->update(['volleyball_stats_pdf' => null]);
        }

        return $this->success(null, 'Volleyball stats PDF deleted successfully');
    }

    /**
     * Delete volleyball ranking image
     */
    public function deleteVolleyballRankingImage(Player $player): JsonResponse
    {
        if ($player->volleyball_ranking_image) {
            $this->mediaService->delete($player->volleyball_ranking_image);
            $player->update(['volleyball_ranking_image' => null]);
        }

        return $this->success(null, 'Volleyball ranking image deleted successfully');
    }

    /**
     * Upload strategy PDF for a player (Admin/Owner only)
     */
    public function uploadPlayerStrategyPdf(\Illuminate\Http\Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
        ]);

        // Delete old file if exists
        if ($player->strategy_pdf) {
            $this->mediaService->delete($player->strategy_pdf);
        }

        $path = $this->mediaService->upload($request->file('file'), 'players/strategy');
        $player->update(['strategy_pdf' => $path]);

        return $this->success(['strategy_pdf' => asset('storage/' . $path)], 'Strategy PDF uploaded successfully');
    }

    /**
     * Delete strategy PDF (Admin/Owner only)
     */
    public function deletePlayerStrategyPdf(Player $player): JsonResponse
    {
        if ($player->strategy_pdf) {
            $this->mediaService->delete($player->strategy_pdf);
            $player->update(['strategy_pdf' => null]);
        }

        return $this->success(null, 'Strategy PDF deleted successfully');
    }
}
