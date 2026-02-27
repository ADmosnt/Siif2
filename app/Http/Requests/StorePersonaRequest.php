<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->esAdministrador();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'documento_identidad'   => 'required|unique:t_personas,documento_identidad|max:11',
        'name'                  => 'required|unique:t_personas,name',
        'password'              => 'required|min:8', // Es mejor validar por mínimo de caracteres que por máximo
        'idgrupo_persona'       => 'required|string|in:RFV,MAY,CLI', // Sé explícito con los tipos válidos
        'idperfil'              => 'required|integer', // o integer si es un ID
        'nombre_persona'        => 'required|string',
        'apellido_persona'      => 'required|string',
        'email'                 => 'required|email|unique:t_personas,email',
        'sexo_genero_persona'   => 'required|in:M,F',
        'idpais'                => 'required|exists:t_paises,idpais',
        'idestado'              => 'required|exists:t_estados,idestado',
        'idciudad'              => 'required|exists:t_ciudades,idCiudad',
        'idespecialidad'        => 'required|exists:t_especialidades,id',
        'idclase_persona'       => 'required|exists:t_clase_personas,id',
        'idfrecuencia'          => 'required|integer',
        'idranking'             => 'required|integer',
        'descuento'             => 'nullable|numeric|between:0,100',
        'telefono_persona'      => 'nullable|string',
        'direccion_domicilio'   => 'nullable|string',
        'idciclos'              => 'nullable|exists:t_ciclos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_persona.string'         => 'El nombre no puede contener números.',
            'apellido_persona.string'       => 'El apellido no puede contener números.',
            'email.unique'                  => 'Ya existe una persona registrada con este correo electrónico.',
            'documento_identidad.unique'    => 'Este documento de identidad ya ha sido registrado.',
            'required'                      => 'El campo :attribute es obligatorio.',
        ];
    }
}
