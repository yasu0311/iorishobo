<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'subject',
        'body',
        'description',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public function renderSubject(array $data = []): string
    {
        return Blade::render($this->subject, $data);
    }

    public function renderBody(array $data = []): string
    {
        return Blade::render($this->body, $data);
    }
}
