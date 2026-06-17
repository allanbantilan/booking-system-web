<template>
    <div class="relative flex min-h-screen items-center justify-center bg-ledger-bg px-4 py-24 text-ledger-text">
        <div class="relative w-full max-w-md">
            <div class="mb-7 flex justify-center"><slot name="logo" /></div>
            <div class="ledger-panel p-6 sm:p-8"><slot /></div>
        </div>
    </div>
</template>
