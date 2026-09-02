<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title_en',
        'title_ar',
        'content_en',
        'content_ar',
        'category_en',
        'category_ar',
        'main_image_path',
        'gallery_images',
        'is_featured',
        'is_active',
        'published_at'
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime'
    ];
}
