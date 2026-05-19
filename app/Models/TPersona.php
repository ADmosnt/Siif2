<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;


class TPersona extends Authenticatable
{
    use Notifiable, HasApiTokens, HasPushSubscriptions;


    // -- Constantes --
    public const TIPO_REPRESENTANTE = 'RFV';
    public const TIPO_GERENTE       = 'GRT';
    public const TIPO_MAYORISTA     = 'MAY';
    public const TIPO_CLIENTE       = 'CLI';
    public const TIPO_SUPERVISOR    = 'SUP';
    public const TIPO_ADMINISTRADOR = 'SIIF';

    
    // -- Propiedades --
    protected $table        ='t_personas';
    protected $primaryKey   ='idPersona';
    public $incrementing    = false;
    public $timestamps      = false;
    public $modulos         ='050201';

    protected $casts = 
    [
        'email_verified_at'         => 'datetime',
        'password'                  => 'hashed',
        'fecha_nacimiento_registro' => 'date:Y-m-d',
        'terminos_condiciones'      => 'boolean',
    ];
    protected $searchable = 
    [
        'nombre_completo_razon_social'
    ];

    // Lista de campos que NO deben convertirse a mayúsculas (ej: emails, códigos sensibles, etc.)
    protected $excludeFromUppercase = [
        'name',
        'password',
        'email',
        'banco_persona',
        'cuenta_principal_persona',
        'banco_persona_internacional',
        'cuenta_internacional_persona',
        'aba',
        'switf',
        'url_foto',
        'url_logo',
        'coordenadas_l',
        'coordenadas_a',
        'terminos_condiciones',
        'email_contacto',
        'remember_token'
    ];

    public function setAttribute($key, $value)
    {
        // Si el campo NO está excluido y es de tipo string, lo converte a mayúsculas
        if (!in_array($key, $this->excludeFromUppercase) && is_string($value)) {
            $value = strtoupper(trim($value));
        }

        return parent::setAttribute($key, $value);
    }
    protected $hidden = [
        'idOperador',
        //'idFabricante',
        'password',
      //  'idempresa_Grupo',
      //  'idsupervisor',
       // 'idcadenas',
        //'idpais',
        //'ididioma',
        //'idclase_persona',
        //'idranking',
        //'idciclos',
        //'idfrecuencia',
    //    'idestado',
      //  'idciudad',
    //    'zona_postal',
      //  'banco_persona',
    //    'cuenta_principal_persona',
      //  'banco_persona_internacional',
    //    'cuenta_internacional_persona',
     //   'aba',
      //  'switf',
    //    'url_foto',
     //   'url_logo',
      //  'coordenadas_l',
    //    'coordenadas_a',
     //   'terminos_condiciones',
      //  'idestatus',
        'remember_token'
    ];

    protected $fillable = [
        'idOperador',
        'idFabricante',
        'idPersona',
        'name',
        'password',
        'idempresa_Grupo',
        'idsupervisor',
        'idcadenas',
        'idpais',
        'ididioma',
        'cod_tipo_persona',
        'idespecialidad',
        'idactividad_negocio',
        'idsubespecialidad',
        'documento_identidad',
        'idgrupo_persona',
        'idclase_persona',
        'idranking',
        'idperfil',
        'idtitulo',
        'nombre_persona',
        'apellido_persona',
        'nombre_completo_razon_social',
        'sexo_genero_persona',
        'fecha_nacimiento_registro',
        'telefono_persona',
        'movil_persona',
        'email',
        'idciclos',
        'idfrecuencia',
        'direccion_domicilio',
        'idestado',
        'idciudad',
        'zona_postal',
        'banco_persona',
        'cuenta_principal_persona',
        'banco_persona_internacional',
        'cuenta_internacional_persona',
        'aba',
        'switf',
        'url_foto',
        'url_logo',
        'coordenadas_l',
        'coordenadas_a',
        'terminos_condiciones',
        'persona_contacto',
        'telefono_contacto',
        'email_contacto',
        'descuento',
        'idestatus',
        'remember_token'
    ];
     
     // --- Metodos de ayuda | comprobar tipo --- ///

    public function esRepresentante(): bool
        {
            return $this->idgrupo_persona === self::TIPO_REPRESENTANTE;
        }
    public function esMayorista(): bool
        {
            return $this->idgrupo_persona === self::TIPO_MAYORISTA;
        }
    public function esCliente(): bool
        {
            return $this->idgrupo_persona === self::TIPO_CLIENTE;
        }
    public function esSupervisor(): bool
        {
            return $this->idgrupo_persona === self::TIPO_SUPERVISOR;
        }
    public function esAdministrador(): bool
        {
            return $this->idgrupo_persona === self::TIPO_ADMINISTRADOR;
        }

    // -- Scopes
    /* Full - Text Search Scope */
    protected function fullTextWildcards($term)
    {
        return str_replace(' ', '*', $term) . '*';
    }

    public function scopeSearch($query, $term)
    {
        $columns = implode(',',$this->searchable);
        $query->whereRaw("MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)" , $this->fullTextWildcards($term));
        return $query;
    }

    /* QUERY BUILDER SCOPES */
    public function scopeTypeRFV($builder)
    {
        return $builder->where('idgrupo_persona', self::TIPO_REPRESENTANTE);
    }

    public function scopeTypeMayorista($builder)
    {
        return $builder->where('idgrupo_persona',self::TIPO_MAYORISTA);
    }

    public  function scopeTypeCliente($builder){
        return $builder->where('idgrupo_persona',self::TIPO_CLIENTE);
    }

    public function scopeTypeSupervisor($builder){
        return $builder->where('idgrupo_persona',self::TIPO_SUPERVISOR);
    }


    // -- Relaciones --
    public function planificadores()
    {
        return $this->hasMany(TPlanificadore::class,'idCliente','idPersona');
    }
    public function Perfil(){
        return $this->belongsTo(TPerfile::class,'idperfil','id');
    }

    public function Dias(){
        return $this->belongsToMany(TDiasVisita::class,'t_persona_dias','idPersona','iddias_visita');
    }

    public function Horarios(){
        return $this->belongsToMany(THorario::class,'t_persona_horario','idPersona','idhorarios');
    }

    public function Clientes(){
        return $this->belongsToMany(TPersona::class,'r_cliente_rfv','id_RFV','id_cliente');
    }

    public function Materiales(){
        return $this->belongsToMany(TProducto::class,'r_clientes_materiales','idcliente','idproducto');
    }

    public function bricks(){
        return $this->belongsToMany(TBrickRuta::class,'t_brick_ruta_personas','idPersona','idbrick');
    }

    public function especialidad(){
        return $this->belongsTo(TEspecialidade::class,'idespecialidad', 'id');
    }

    public function pais(){
        return $this->belongsTo(TPaise::class, 'idpais');
    }

    public function estado(){
        return $this->belongsTo(TEstado::class, 'idestado');
    }

    public function ciudad(){
        return $this->belongsTo(TCiudade::class, 'idciudad');
    }

     public function tipo(){
        return $this->belongsTo(TTipoPersona::class, 'cod_tipo_persona', 'id');
    }

    public function clase(){
        return $this->belongsTo(TClasePersona::class, 'idclase_persona', 'id');
    }

    public function ranking(){
        return $this->belongsTo(TRankingCliente::class, 'idranking', 'id');
    }

    public function frecuencia(){
        return $this->belongsTo(TFrecuenciaVisita::class, 'idfrecuencia', 'id');
    }

    public function ciclo(){
        return $this->belongsTo(TCiclo::class, 'idciclos', 'id');
    }
    /* GLOBAL FILTER OPERADOR , FABRICANTE TO CONSULT MYSQL*/
    // -- Metodo Boot --

   protected static function boot()
    {
        parent::boot();
        // static::addGlobalScope(new OperadorFabricante);
    }

    public function clientesRfv()
{
    return $this->hasMany(RClienteRfv::class, 'id_cliente', 'idPersona');
}

}
