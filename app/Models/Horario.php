<?php
// app/Models/Horario.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = ['hora_inicio', 'hora_fim', 'tipo'];

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }
}
