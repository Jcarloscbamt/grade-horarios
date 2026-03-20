<?php
// app/Models/Sala.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    protected $table = 'salas';

    protected $fillable = ['nome', 'tipo', 'capacidade', 'bloco'];

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }
}
