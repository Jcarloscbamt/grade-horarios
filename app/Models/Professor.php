<?php
// app/Models/Professor.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professor extends Model
{
    protected $table = 'professores';
    
    protected $fillable = ['nome', 'email', 'telefone', 'cpf'];

    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }
}
