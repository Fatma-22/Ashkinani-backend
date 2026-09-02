<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Meta data endpoints
    Route::get('/players/sports', [\App\Http\Controllers\Api\V1\PlayerController::class, 'sports']);
    Route::get('/players/nationalities', [\App\Http\Controllers\Api\V1\MetaController::class, 'nationalities']);
    Route::get('/players/positions', [\App\Http\Controllers\Api\V1\PlayerController::class, 'positions']);
    Route::get('/players/deal-statuses', [\App\Http\Controllers\Api\V1\PlayerController::class, 'dealStatuses']);

    Route::get('/public/landing', [\App\Http\Controllers\Api\V1\PublicContentController::class, 'getLandingPageData']);
    Route::get('/public/news', [\App\Http\Controllers\Api\V1\PublicContentController::class, 'getAllNews']);
    Route::get('/public/news/{news}', [\App\Http\Controllers\Api\V1\PublicContentController::class, 'getNewsDetails']);
    Route::get('/public/deals', [\App\Http\Controllers\Api\V1\DealController::class, 'publicIndex']);
    Route::post('/public/players', [\App\Http\Controllers\Api\V1\PlayerController::class, 'publicStore']);
    Route::post('/public/track-visit', [\App\Http\Controllers\Api\V1\PublicContentController::class, 'trackVisit']);
    Route::post('/public/players/{player}/photos', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'publicUploadPhoto']);
    Route::post('/public/players/{player}/cv', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'publicUploadCV']);
    Route::get('/public/sponsors', [\App\Http\Controllers\Api\V1\SponsorController::class, 'index']);

    // Public archived players endpoint (expired contracts)
    Route::get('/players/archived', [\App\Http\Controllers\Api\V1\PlayerController::class, 'archived']);

    // Public Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth Info
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        /**
         * Owner Only Routes
         */
        Route::middleware('role:OWNER')->group(function () {
            Route::get('/owner/financials/stats', [\App\Http\Controllers\Api\V1\OwnerController::class, 'getFinancialStats']);
            Route::get('/owner/financials', [\App\Http\Controllers\Api\V1\OwnerController::class, 'getFinancialRecords']);
            Route::post('/owner/financials', [\App\Http\Controllers\Api\V1\OwnerController::class, 'storeFinancialRecord']);
            Route::put('/owner/financials/{record}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'updateFinancialRecord']);
            Route::delete('/owner/financials/{record}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'destroyFinancialRecord']);

            Route::get('/owner/admins', [\App\Http\Controllers\Api\V1\OwnerController::class, 'getAdmins']);
            Route::post('/owner/admins', [\App\Http\Controllers\Api\V1\OwnerController::class, 'storeAdmin']);
            Route::put('/owner/admins/{admin}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'updateAdmin']);
            Route::delete('/owner/admins/{admin}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'destroyAdmin']);

            Route::get('/owner/employees', [\App\Http\Controllers\Api\V1\OwnerController::class, 'getEmployees']);
            Route::post('/owner/employees', [\App\Http\Controllers\Api\V1\OwnerController::class, 'storeEmployee']);
            Route::put('/owner/employees/{employee}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'updateEmployee']);
            Route::delete('/owner/employees/{employee}', [\App\Http\Controllers\Api\V1\OwnerController::class, 'destroyEmployee']);
        });

        /**
         * Admin & Owner Routes
         */
        Route::middleware('role:ADMIN,OWNER')->group(function () {
            // Administrative CRUDs - with permission checks
            Route::apiResource('agents', \App\Http\Controllers\Api\V1\AgentController::class)
                ->middleware('adminPermission:canAddAgents')
                ->only(['store']);
            Route::apiResource('agents', \App\Http\Controllers\Api\V1\AgentController::class)
                ->middleware('adminPermission:canEditAgents')
                ->only(['update']);
            Route::apiResource('agents', \App\Http\Controllers\Api\V1\AgentController::class)
                ->middleware('adminPermission:canDeleteAgents')
                ->only(['destroy']);
            Route::apiResource('agents', \App\Http\Controllers\Api\V1\AgentController::class)
                ->only(['index', 'show']);

            Route::apiResource('contracts', \App\Http\Controllers\Api\V1\ContractController::class)
                ->middleware('adminPermission:canAddAgents')
                ->only(['store']);
            Route::apiResource('contracts', \App\Http\Controllers\Api\V1\ContractController::class)
                ->middleware('adminPermission:canEditAgents')
                ->only(['update']);
            Route::apiResource('contracts', \App\Http\Controllers\Api\V1\ContractController::class)
                ->middleware('adminPermission:canDeleteAgents')
                ->only(['destroy']);
            Route::apiResource('contracts', \App\Http\Controllers\Api\V1\ContractController::class)
                ->only(['index', 'show']);

            Route::get('/deals/stats', [\App\Http\Controllers\Api\V1\DealController::class, 'stats']);
            Route::apiResource('deals', \App\Http\Controllers\Api\V1\DealController::class)->only(['index', 'show']);
            Route::post('/deals', [\App\Http\Controllers\Api\V1\DealController::class, 'store'])->middleware('adminPermission:canAddDeals');
            Route::put('/deals/{deal}', [\App\Http\Controllers\Api\V1\DealController::class, 'update'])->middleware('adminPermission:canEditDeals');
            Route::delete('/deals/{deal}', [\App\Http\Controllers\Api\V1\DealController::class, 'destroy'])->middleware('adminPermission:canDeleteDeals');

            // Content Management (Sponsors, Discounts, Ads, News)
            Route::apiResource('sponsors', \App\Http\Controllers\Api\V1\SponsorController::class)
                ->middleware('adminPermission:canManageSponsors');
            Route::apiResource('discounts', \App\Http\Controllers\Api\V1\DiscountController::class);
            Route::apiResource('ads', \App\Http\Controllers\Api\V1\AdController::class);
            Route::apiResource('news', \App\Http\Controllers\Api\V1\NewsController::class);
            Route::apiResource('federations', \App\Http\Controllers\Api\V1\FederationController::class)
                ->middleware('adminPermission:canManageFederations');
            Route::apiResource('clubs', \App\Http\Controllers\Api\V1\ClubController::class)
                ->middleware('adminPermission:canManageClubs');
            Route::apiResource('meetings', \App\Http\Controllers\Api\V1\MeetingController::class);
            Route::get('/members', [\App\Http\Controllers\Api\V1\MemberController::class, 'index']);
            Route::put('/members/{user}/toggle-status', [\App\Http\Controllers\Api\V1\MemberController::class, 'toggleStatus']);
            Route::delete('/members/{user}', [\App\Http\Controllers\Api\V1\MemberController::class, 'destroy']);

            // Nutrition & Performance Department
            Route::prefix('nutrition')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\NutritionController::class, 'getStats']);
                Route::get('/player/{player}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'getPlayerFile']);
                
                // Management routes (Create/Update/Delete)
                Route::middleware('adminPermission:canManageNutrition')->group(function () {
                    Route::post('/physical-report', [\App\Http\Controllers\Api\V1\NutritionController::class, 'storePhysicalReport']);
                    Route::put('/physical-report/{report}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'updatePhysicalReport']);
                    Route::delete('/physical-report/{report}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'destroyPhysicalReport']);
                    
                    Route::post('/progress-photo', [\App\Http\Controllers\Api\V1\NutritionController::class, 'uploadProgressPhoto']);
                    Route::put('/progress-photo/{photo}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'updateProgressPhoto']);
                    Route::delete('/progress-photo/{photo}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'destroyProgressPhoto']);
                    
                    Route::post('/nutrition-program', [\App\Http\Controllers\Api\V1\NutritionController::class, 'storeNutritionProgram']);
                    Route::put('/nutrition-program/{program}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'updateNutritionProgram']);
                    Route::delete('/nutrition-program/{program}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'destroyNutritionProgram']);
                    
                    Route::post('/training-program', [\App\Http\Controllers\Api\V1\NutritionController::class, 'storeTrainingProgram']);
                    Route::put('/training-program/{program}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'updateTrainingProgram']);
                    Route::delete('/training-program/{program}', [\App\Http\Controllers\Api\V1\NutritionController::class, 'destroyTrainingProgram']);
                });
            });
        });

        /**
         * Agent, Admin & Owner Dashboard Routes
         */
        Route::middleware('role:AGENT,ADMIN,OWNER')->group(function () {
            Route::get('/dashboard/stats', [\App\Http\Controllers\Api\V1\DashboardController::class, 'getStats']);
            Route::get('/dashboard/market-value', [\App\Http\Controllers\Api\V1\DashboardController::class, 'getMarketValueDistribution']);
            Route::get('/dashboard/contract-status', [\App\Http\Controllers\Api\V1\DashboardController::class, 'getContractStatusData']);
            Route::get('/dashboard/contract-expiry', [\App\Http\Controllers\Api\V1\DashboardController::class, 'getContractExpiryTimeline']);
            Route::get('/dashboard/deal-types', [\App\Http\Controllers\Api\V1\DashboardController::class, 'getDealTypeStats']);
        });

        /**
         * Agent, Admin & Owner General Routes
         */
        Route::middleware('role:AGENT,ADMIN,OWNER')->group(function () {
            // Player management - with permission checks
            Route::post('/players', [\App\Http\Controllers\Api\V1\PlayerController::class, 'store'])
                ->middleware('adminPermission:canAddPlayers');
            Route::put('/players/{player}', [\App\Http\Controllers\Api\V1\PlayerController::class, 'update'])
                ->middleware('adminPermission:canEditPlayers');
            Route::delete('/players/{player}', [\App\Http\Controllers\Api\V1\PlayerController::class, 'destroy'])
                ->middleware('adminPermission:canDeletePlayers');

            // Media Management
            Route::post('/players/{player}/photos', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'uploadPhoto'])
                ->middleware('adminPermission:canEditPlayers');
            Route::post('/players/{player}/photos/{photo}/main', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'setMainPhoto'])
                ->middleware('adminPermission:canEditPlayers');
            Route::delete('/players/{player}/photos/{photo}', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'deletePhoto'])
                ->middleware('adminPermission:canEditPlayers');

            Route::post('/players/{player}/documents', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'uploadDocument'])
                ->middleware('adminPermission:canEditPlayers');
            Route::delete('/players/{player}/documents/{document}', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'deleteDocument'])
                ->middleware('adminPermission:canEditPlayers');

            Route::post('/players/{player}/cv', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'uploadCV'])
                ->middleware('adminPermission:canEditPlayers');
            Route::delete('/players/{player}/cv', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'deleteCV'])
                ->middleware('adminPermission:canEditPlayers');

            Route::post('/players/{player}/club-contracts/{clubContract}/file', [\App\Http\Controllers\Api\V1\PlayerMediaController::class, 'uploadClubContractFile'])
                ->middleware('adminPermission:canEditPlayers');
        });
        
        Route::post('/players/{player}/generate-share-token', [\App\Http\Controllers\Api\V1\PlayerController::class, 'generateShareToken']);

        // Shared Protected Routes
        Route::get('/players', [\App\Http\Controllers\Api\V1\PlayerController::class, 'index'])->withoutMiddleware('auth:sanctum'); // Publicly searchable
        Route::get('/players/{player}', [\App\Http\Controllers\Api\V1\PlayerController::class, 'show'])->withoutMiddleware('auth:sanctum'); // Publicly viewable

        /**
         * Member Profile / CV Routes
         */
        Route::get('/me/player-cv', [\App\Http\Controllers\Api\V1\MemberProfileController::class, 'getPlayerCV']);
        Route::post('/me/player-cv', [\App\Http\Controllers\Api\V1\MemberProfileController::class, 'createPlayerCV']);
        Route::put('/me/player-cv/{player}', [\App\Http\Controllers\Api\V1\MemberProfileController::class, 'updatePlayerCV']);
        Route::post('/me/player-cv/{player}/photo', [\App\Http\Controllers\Api\V1\MemberProfileController::class, 'uploadPhoto']);
        Route::post('/me/player-cv/{player}/cv-document', [\App\Http\Controllers\Api\V1\MemberProfileController::class, 'uploadCVDocument']);

        // Member's own Nutrition & Performance data
        Route::get('/me/nutrition', [\App\Http\Controllers\Api\V1\NutritionController::class, 'getMyNutritionFile']);

    });
});
