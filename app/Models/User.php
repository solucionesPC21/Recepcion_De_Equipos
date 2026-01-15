<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role; // Añade esta línea al inicio

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'usuario',
        'password',
        'role_id' // Añadido para el sistema de roles
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Relación con el modelo Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verifica si el usuario tiene un rol específico
     * @param string|array $roleName Nombre del rol o array de nombres
     * @return bool
     */
    public function hasRole($roleName): bool
    {
        if (is_array($roleName)) {
            return in_array(optional($this->role)->nombre, $roleName);
        }
        
        return optional($this->role)->nombre === $roleName;
    }

    /**
     * Verifica si el usuario es administrador
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role_id === 1; // Asumiendo que 1 es el ID para admin
    }

    /**
     * Scope para usuarios con un rol específico
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|int $role Puede ser ID o nombre del rol
     */
    public function scopeWithRole($query, $role)
    {
        if (is_numeric($role)) {
            return $query->where('role_id', $role);
        }
        
        return $query->whereHas('role', function($q) use ($role) {
            $q->where('nombre', $role);
        });
    }

    /**
     * Scope para usuarios administradores
     */
    public function scopeAdmins($query)
    {
        return $query->withRole('admin');
    }

    /**
     * Scope para usuarios normales
     */
    public function scopeRegularUsers($query)
    {
        return $query->withRole('usuario');
    }

    public function getNameAttribute()
    {
        return $this->attributes['nombre'];
    }

    /**
     * Mutator para compatibilidad con Laravel
     */
    public function setNameAttribute($value)
    {
        $this->attributes['nombre'] = $value;
    }
}