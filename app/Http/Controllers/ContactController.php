<?php

//app/Http/Controllers/ContactController.php
//Proceso: Su función principal es recibir los datos, validarlos y dar la orden de enviar el correo


namespace App\Http\Controllers;

use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Enviar correo de contacto
     */
    public function send(Request $request)
    {
        // Validar los datos 
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'asunto' => 'required|string|max:255',
            'mensaje' => 'required|string|max:1000',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'asunto.required' => 'El asunto es obligatorio.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'mensaje.max' => 'El mensaje no debe exceder los 1000 caracteres.',
        ]);

        //Si la validación falla, Inertia redirigirá con los errores
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Obtener el correo de destino (configura MAIL_CONTACT_TO en .env)
            $toEmail = config('mail.contact_to', config('mail.from.address'));
            
            // Enviar el correo
            Mail::to($toEmail)->send(new ContactMail($request->all()));
            
            return back()->with('success', '¡Mensaje enviado con éxito! Te contactaremos pronto.');
                
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de contacto: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al enviar el mensaje. Por favor, inténtalo de nuevo.')->withInput();
        }
    }
}