<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'active'];
    
    /**
     * الحصول على الأقسام التي تنتمي لهذا التصنيف
     */
    public function partitions()
    {
        return $this->hasMany(Partition::class);
    }
}
