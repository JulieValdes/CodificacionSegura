<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">


<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@3.0.0/dist/flowbite.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css"  rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@1.4.1/dist/flowbite.min.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.0.0/dist/flowbite.min.js"></script>   

    <!-- Script de reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <section class="bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0  bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-800 selection:bg-red-500 selection:text-white">
            <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        Crea una cuenta
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{ route('register') }}" method="POST">
                        @csrf <!-- Token CSRF para protección -->
                        <!-- Campo para el nombre -->
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nombre</label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                placeholder="María" 
                                required 
                                minlength="3" 
                                maxlength="30" 
                                pattern="[A-Za-zÁÉÍÓÚáéíóúÜüÑñ\s]+" 
                                title="El nombre debe tener entre 3-30 carácteres y sólo caracteres del alfabeto.">
                            @error('name')
                                <small style="color: red" class="font-bold">{{$message}}</small>
                            @enderror
                        </div>
                        <!-- Campo para el apellido -->
                        <div>
                            <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Apellido</label>
                            <input 
                                type="text" 
                                name="last_name" 
                                id="last_name" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                placeholder="Gonzales" 
                                required 
                                minlength="3" 
                                maxlength="30" 
                                pattern="[A-Za-zÁÉÍÓÚáéíóúÜüÑñ\s]+"
                                title="El apellido debe tener entre 3-30 carácteres y sólo caracteres del alfabeto.">
                            @error('last_name')
                                <small style="color: red" class="font-bold">{{$message}}</small>
                            @enderror
                        </div>
                        <!-- Campo para el correo electrónico -->
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Correo</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                placeholder="name@company.com" 
                                required 
                                maxlength="60" 
                                title="Ingresa un correo válido.">
                            @error('email')
                                <small style="color: red" class="font-bold">{{$message}}</small>
                            @enderror
                        </div>
                        <!-- Campo para la contraseña -->
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                placeholder="••••••••" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                required 
                                minlength="8" 
                                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*#?&]).{8,}" 
                                title="La contraseña debe contener al menos una letra mayúscula, una letra minúscula, un número y un carácter especial(@$!%*#?&).">
                            @error('password')
                                <small style="color: red" class="font-bold">{{$message}}</small>
                            @enderror
                        </div>
                        <!-- Campo para confirmar la contraseña -->
                        <div>
                            <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirma tu contraseña</label>
                            <input 
                                type="password" 
                                name="password_confirmation" 
                                id="password_confirmation" 
                                placeholder="••••••••" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                                required 
                                minlength="8" 
                                pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*#?&]).{8,}" 
                                title="Por favor confirma tu contraseña.">
                            @error('password_confirmation')
                                <small style="color: red" class="font-bold">{{$message}}</small>
                            @enderror
                        </div>
                        <!-- Campo para reCAPTCHA -->
                        <div>
                            <div class="g-recaptcha block w-full" data-sitekey="6LeSXsYqAAAAABxX1_WMUS0RxpSP1uIP3jVsONb9"></div>
                            @error('g-recaptcha-response')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Botón de registro -->
                        <button 
                            type="submit" 
                            class="w-full text-white bg-blue-700 hover:bg-blue-200 hover:text-gray-700 hover:ring-blue-600 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Registrate.</button>
                        <!-- Enlace para iniciar sesión -->
                        <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                            ¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-500">Inicia Sesion</a>
                        </p>
                    </form>                                      
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<style>
    .g-recaptcha {
    display: block !important;
    margin: 10px 0; /* Añade margen para mejor visualización */
}
</style>