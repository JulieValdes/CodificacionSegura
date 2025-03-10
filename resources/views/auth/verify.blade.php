<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de Correo</title>
    <!-- Incluyendo TailwindCSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Script de reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-800">
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h1 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-4">Verifica tu correo electrónico</h1>

            @if (session('success'))
                <div class="text-green-500 mb-4 text-center">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="text-red-500 mb-4 text-center">
                    {{ session('error') }}
                </div>
            @endif

            <p class="text-center text-gray-700 dark:text-gray-300 mb-4">
                Se ha enviado un código de verificación a tu correo electrónico. Por favor, revisa tu bandeja de entrada.
            </p>

            <!-- Formulario para reenviar el código -->
            <form action="{{ route('verification.resend') }}" method="POST" class="mb-4">
                @csrf
                <input type="hidden" name="email" value="{{ session('email') }}">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Reenviar código de verificación
                </button>
            </form>

            <!-- Formulario para ingresar el código de verificación -->
            <form action="{{ route('verification.verify') }}" method="POST">
                @csrf
                <label for="code" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Ingresa el código de verificación:</label>
                @error('code')
                    <small style="color: red" class="font-bold">{{ $message }}</small>
                @enderror
                <input type="text" name="code" id="code" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 mb-4" required>
                <input type="hidden" name="email" value="{{ session('email') }}">
                <!-- Campo para reCAPTCHA -->
                <div>
                    <div class="g-recaptcha block my-1 w-full" data-sitekey="6LeSXsYqAAAAABxX1_WMUS0RxpSP1uIP3jVsONb9"></div>
                    @error('g-recaptcha-response')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="mt-1 w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Verificar
                </button>
            </form>
        </div>
    </div>
</body>
</html>