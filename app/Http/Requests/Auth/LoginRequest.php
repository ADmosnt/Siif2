<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Throwable;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        try {
            $this->ensureIsNotRateLimited();


            $authResult = $this->attemptAuthentication();

            if (!$authResult) {
                $this->handleFailedAuthentication();
            }

            $user = Auth::user();

            if ($user) {
                if ($user->idestatus == 0) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'name' => 'Su cuenta ha sido desactivada. Contacte al administrador.',
                    ]);
                }

                $idFabricante = $user->idFabricante;

                //Almacenar el idFabricante en la sesión
                session(['id_fabricante_logueado' => $idFabricante]);

                Log::info('Usuario autenticado con éxito', [
                    'user_name' => $user->name,
                    'id_fabricante' => $idFabricante,
                    'ip' => $this->ip()
                ]);

            } else {

                Log::error('Auth::attempt fue exitoso pero Auth::user() es nulo.');
                throw ValidationException::withMessages([
                    'name' => 'Error interno al recuperar datos del usuario autenticado.',
                ]);
            }


            // Limpiar el contador de intentos fallidos si el login fue exitoso
            RateLimiter::clear($this->throttleKey());

        } catch (ValidationException $e) {
            // Re-lanzar ValidationException (errores de validación y rate limiting)
            throw $e;
        } catch (Throwable $e) {
            // Capturar cualquier otro error inesperado
            $this->handleUnexpectedError($e);
        }
    }

    /**
     * Intentar autenticación con manejo seguro de errores
     */
    private function attemptAuthentication(): bool
    {
        try {
            return Auth::attempt($this->only('name', 'password'));
        } catch (Exception $e) {
            Log::error('Error durante el proceso de autenticación:', [
                'name' => $this->input('name'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent()
            ]);
            return false;
        }
    }

    /**
     * Manejar autenticación fallida
     */
    private function handleFailedAuthentication(): void
    {
        RateLimiter::hit($this->throttleKey());
        Log::warning('Intento de autenticación fallido:', [
            'name' => $this->input('name'),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
        throw ValidationException::withMessages([
            'name' => trans('auth.failed'),
        ]);
    }

    /**
     * Manejar errores inesperados
     */
    private function handleUnexpectedError(Throwable $e): void
    {
        Log::error('Error inesperado durante el login:', [
            'name' => $this->input('name'),
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages([
            'name' => 'Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        Log::warning('Usuario bloqueado por demasiados intentos:', [
            'name' => $this->input('name'),
            'ip' => $this->ip(),
            'seconds_remaining' => $seconds,
            'timestamp' => now()->toISOString()
        ]);
        throw ValidationException::withMessages([
            'name' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('name')).'|'.$this->ip());
    }
}