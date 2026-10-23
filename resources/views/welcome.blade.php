<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Droguería Vida Sana — Segundo Parcial</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code { font-family: 'Fira Code', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl text-center">
        <!-- Badge -->
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 mb-6">
            Segundo Parcial &bull; Software de Gestión Empresarial
        </span>

        <!-- Logo / Icono -->
        <div class="w-16 h-16 bg-rose-600 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-4 shadow-lg shadow-rose-900/50">
            💊
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">
            Droguería <span class="text-rose-400">Vida Sana</span>
        </h1>
        <p class="text-slate-400 text-sm mb-6 max-w-lg mx-auto leading-relaxed">
            Módulo de gestión de inventarios con algoritmo sanitario <strong>FEFO</strong> (First Expired, First Out), transacciones atómicas con <code>DB::transaction</code> y control de acceso con <strong>Spatie Roles & Permisos</strong>.
        </p>

        <!-- Tarjetas de Info del Parcial -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-8 text-left">
            <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/60">
                <span class="text-slate-400 block font-semibold mb-1">👨‍💻 Integrantes:</span>
                <strong class="text-white block">Esteban Molina & Heiber Lozano</strong>
            </div>
            <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/60">
                <span class="text-slate-400 block font-semibold mb-1">🎓 Institución:</span>
                <strong class="text-emerald-400 block">COTECNOVA &bull; 2026</strong>
            </div>
            <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/60">
                <span class="text-slate-400 block font-semibold mb-1">🌿 Rama Oficial de Entrega:</span>
                <code class="text-rose-400 font-mono font-bold">parcial2</code>
            </div>
            <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/60">
                <span class="text-slate-400 block font-semibold mb-1">📁 Documentación:</span>
                <code class="text-sky-400 font-mono">docs/parcial2/</code>
            </div>
        </div>

        <p class="text-[11px] text-slate-500">
            Este proyecto evalúa arquitectura backend, modelos de datos, seguridad RBAC y transaccionalidad mediante consola Tinker y MySQL.
        </p>
    </div>
</body>
</html>
