<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <p class="kicker">Admin dashboard</p>
        <h2 class="mt-1 font-display text-2xl font-bold tracking-tight">Masuk</h2>
        <p class="mt-1 text-sm text-ink-soft">Gunakan akun email dan kata sandi Anda.</p>

        <div v-if="status" class="mt-4 rounded-lg border border-ok/30 bg-ok/5 px-3 py-2 text-sm text-ok">
            {{ status }}
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin@idesegarprinting.id"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Kata sandi" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-ink-soft">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    Ingat saya
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="font-mono text-xs text-accent hover:text-accent-dark">
                    Lupa kata sandi?
                </Link>
            </div>

            <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                {{ form.processing ? 'Memproses…' : 'Masuk' }}
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-ink-faint">
            Belum punya akun?
            <Link href="/register" class="font-medium text-accent hover:text-accent-dark">Daftar</Link>
        </p>
        <p class="mt-4 border-t border-line/60 pt-4 font-mono text-[10px] text-ink-faint">
            Akun awal: admin@idesegarprinting.id / password
        </p>
    </GuestLayout>
</template>
