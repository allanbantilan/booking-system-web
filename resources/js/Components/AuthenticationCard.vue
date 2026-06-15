<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-ledger-bg px-4 py-24 text-ledger-text">
        <div class="pointer-events-none absolute inset-0 ledger-grid opacity-25" />
        <div class="pointer-events-none absolute left-[15%] top-20 size-72 rounded-full bg-cyan-500/15 blur-3xl" />
        <div class="pointer-events-none absolute bottom-10 right-[12%] size-64 rounded-full bg-orange-500/15 blur-3xl" />
        <div class="relative w-full max-w-md">
            <div class="mb-7 flex justify-center"><slot name="logo" /></div>
            <div class="ledger-panel rounded-3xl p-6 sm:p-8"><slot /></div>
        </div>
    </div>
</template>
