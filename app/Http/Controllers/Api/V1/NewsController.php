<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Requests\News\UpdateNewsRequest;
use App\Http\Resources\V1\NewsResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return NewsResource::collection(News::latest('published_at')->paginate(20));
    }

    public function store(StoreNewsRequest $request): NewsResource
    {
        $validated = $request->validated();

        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('news/main', 'public');
            $validated['main_image_path'] = $path;
        }

        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('news/gallery', 'public');
            }
        }
        $validated['gallery_images'] = $gallery;

        if (!isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $news = News::create($validated);
        return new NewsResource($news);
    }

    public function show(News $news): NewsResource
    {
        return new NewsResource($news);
    }

    public function update(UpdateNewsRequest $request, News $news): NewsResource
    {
        $validated = $request->validated();

        if ($request->hasFile('main_image')) {
            if ($news->main_image_path)
                Storage::disk('public')->delete($news->main_image_path);
            $path = $request->file('main_image')->store('news/main', 'public');
            $validated['main_image_path'] = $path;
        }

        if ($request->hasFile('gallery')) {
            if ($news->gallery_images) {
                foreach ($news->gallery_images as $oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $gallery = [];
            foreach ($request->file('gallery') as $file) {
                $gallery[] = $file->store('news/gallery', 'public');
            }
            $validated['gallery_images'] = $gallery;
        }

        $news->update($validated);
        return new NewsResource($news);
    }

    public function destroy(News $news)
    {
        if ($news->main_image_path)
            Storage::disk('public')->delete($news->main_image_path);
        if ($news->gallery_images) {
            foreach ($news->gallery_images as $path) {
                Storage::disk('public')->delete($path);
            }
        }
        $news->delete();
        return response()->json(null, 204);
    }
}
