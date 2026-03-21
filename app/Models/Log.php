<?php
// app/Models/Log.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    public $timestamps = false;

    protected $table = 'logs';

    protected $fillable = [
        'user_id', 'user_name', 'acao', 'modulo', 'descricao', 'ip', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper estático para registrar ação facilmente
    public static function registrar(string $acao, string $modulo, string $descricao): void
    {
        static::create([
            'user_id'    => auth()->id(),
            'user_name'  => auth()->user()->name ?? 'Sistema',
            'acao'       => $acao,
            'modulo'     => $modulo,
            'descricao'  => $descricao,
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
