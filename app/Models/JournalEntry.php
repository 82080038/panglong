<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['journal_no', 'entry_date', 'description', 'reference_type', 'reference_id', 'status', 'created_by'];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
