<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * مجلد تخزين صور التصنيفات على قرص public
     */
    public const IMAGE_DIRECTORY = 'categories';

    protected $fillable = ['name', 'slug', 'icon', 'image', 'description', 'active'];

    /**
     * إرجاع الرابط الكامل للصورة (المسارات النسبية تُحوّل إلى رابط التخزين، والروابط الخارجية تُعاد كما هي)
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => match (true) {
                blank($value) => null,
                Str::startsWith($value, ['http://', 'https://']) => $value,
                default => asset('storage/' . $value),
            },
        );
    }

    /**
     * حذف ملف الصورة المخزن محلياً (إن وجد)
     */
    public function deleteImageFile(): void
    {
        $path = $this->getRawOriginal('image');

        if (filled($path) && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * الحصول على الأقسام التي تنتمي لهذا التصنيف
     */
    public function partitions()
    {
        return $this->hasMany(Partition::class);
    }
}
