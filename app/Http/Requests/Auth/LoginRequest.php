<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Infrastructure\Remote\RemoteAuthClient;
use App\Models\User;
use App\Support\LocalSqlite;

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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'documento' => ['required', 'string', 'max:20'],
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
        $this->ensureIsNotRateLimited();

        // En local, asegura que el sqlite existe antes de intentar persistir el usuario sombra.
        LocalSqlite::ensureDatabaseFileExists();

        $documento = Str::upper((string) $this->input('documento', ''));
        $documento = preg_replace('/[^A-Z0-9]/', '', $documento) ?: '';

        try {
            $remote = app(RemoteAuthClient::class)->login($documento, (string) $this->input('password'));
        } catch (\Throwable $e) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['documento' => (string) $e->getMessage()]);
        }

        $remoteUser = $remote['usuario'];
        $remoteId = (int) ($remoteUser['id'] ?? 0);
        if ($remoteId <= 0) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['documento' => trans('auth.failed')]);
        }

        $local = User::query()->where('remote_usuario_id', $remoteId)->first();
        if (!$local) {
            $local = User::query()->where('numero_documento', $documento)->first();
        }
        if (!$local) {
            $local = new User();
            $local->password = Hash::make(Str::random(64));
        }

        $local->remote_usuario_id = $remoteId;
        $local->numero_documento = $documento;
        $local->name = (string) ($remoteUser['nombre'] ?? ('Usuario '.$documento));
        $local->email = (string) ($remoteUser['email'] ?? ($local->email ?: ('u'.$documento.'@local.invalid')));
        $local->save();

        // Guardamos token de usuario remoto en sesión para consumir la API con identidad.
        session(['remote_user_token' => $remote['token']]);

        Auth::login($local, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
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

        throw ValidationException::withMessages([
            'documento' => trans('auth.throttle', [
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
        return Str::transliterate(Str::lower((string) $this->string('documento')).'|'.$this->ip());
    }
}
