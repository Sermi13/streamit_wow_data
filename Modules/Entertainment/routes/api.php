<?php

use Illuminate\Support\Facades\Route;
use Modules\Entertainment\Http\Controllers\API\EntertainmentsController;
use Modules\Entertainment\Http\Controllers\API\WatchlistController;
use Modules\Entertainment\Http\Controllers\API\ReviewController;


Route::get('get-rating', [ReviewController::class, 'getRating']);
Route::get('movie-list', [EntertainmentsController::class, 'movieList']);
Route::get('v2/movie-list', [EntertainmentsController::class, 'movieListV2']);
Route::get('movie-details', [EntertainmentsController::class, 'movieDetails']);
Route::get('tvshow-list', [EntertainmentsController::class, 'tvshowList']);
Route::get('v2/tvshow-list', [EntertainmentsController::class, 'tvshowListV2']);
Route::get('tvshow-details', [EntertainmentsController::class, 'tvshowDetails']);
Route::get('episode-list', [EntertainmentsController::class, 'episodeList']);
Route::get('episode-details', [EntertainmentsController::class, 'episodeDetails']);
Route::get('search-list', [EntertainmentsController::class, 'searchList']);
Route::get('get-search', [EntertainmentsController::class, 'getSearch']);
Route::get('coming-soon', [EntertainmentsController::class, 'comingSoon']);

// Admin routes - require admin token
Route::group(['prefix' => 'admin', 'middleware' => 'admin.token'], function () {
    Route::post('watchlist/add', [\Modules\Entertainment\Http\Controllers\API\AdminWatchlistController::class, 'addToWatchlist']);
    Route::post('watchlist/remove', [\Modules\Entertainment\Http\Controllers\API\AdminWatchlistController::class, 'removeFromWatchlist']);
    Route::get('watchlist/user', [\Modules\Entertainment\Http\Controllers\API\AdminWatchlistController::class, 'getUserWatchlist']);
    Route::delete('watchlist/clear', [\Modules\Entertainment\Http\Controllers\API\AdminWatchlistController::class, 'clearUserWatchlist']);
});


Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('watch-list', [WatchlistController::class, 'watchList']);
    Route::post('save-watchlist', [WatchlistController::class, 'saveWatchList']);
    Route::post('delete-watchlist', [WatchlistController::class, 'deleteWatchList']);

    Route::post('save-rating', [ReviewController::class, 'saveRating'])->name('save-rating');
    Route::post('delete-rating', [ReviewController::class, 'deleteRating'])->name('delete-rating');
    Route::put('update-rating', [ReviewController::class, 'update'])->name('update-rating');

    Route::post('save-likes', [ReviewController::class, 'saveLikes']);
    Route::post('save-download', [EntertainmentsController::class, 'saveDownload']);
    Route::post('delete-download', [EntertainmentsController::class, 'deleteDownload']);


    Route::get('continuewatch-list', [WatchlistController::class, 'continuewatchList']);
    Route::post('save-continuewatch', [WatchlistController::class, 'saveContinueWatch']);
    Route::post('delete-continuewatch', [WatchlistController::class, 'deleteContinueWatch']);

    Route::post('save-reminder', [EntertainmentsController::class, 'saveReminder']);
    Route::post('delete-reminder', [EntertainmentsController::class, 'deleteReminder']);

    Route::post('save-entertainment-views', [EntertainmentsController::class, 'saveEntertainmentViews']);
});
?>
