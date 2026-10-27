<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
    app: Object,
});

const profileForm = useForm({ name: props.user.name, email: props.user.email });

function saveProfile() {
    profileForm.put('/pengaturan/profil', { preserveScroll: true });
}

const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

function savePassword() {
    passwordForm.put('/pengaturan/sandi', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <AppLayout>
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- profil -->
            <div class="panel p-6">
                <p class="kicker">Akun</p>
                <h2 class="mt-1 font-display text-lg font-bold">Profil pengguna</h2>
                <form @submit.prevent="saveProfile" class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="s-name" value="Nama" />
                        <TextInput id="s-name" v-model="profileForm.name" class="mt-1 block w-full" required />
                        <InputError :message="profileForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="s-email" value="Email" />
                        <TextInput id="s-email" v-model="profileForm.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="profileForm.errors.email" class="mt-1" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary" :disabled="profileForm.processing">Simpan profil</button>
                    </div>
                </form>
            </div>

            <!-- sandi -->
            <div class="panel p-6">
                <p class="kicker">Keamanan</p>
                <h2 class="mt-1 font-display text-lg font-bold">Ubah kata sandi</h2>
                <form @submit.prevent="savePassword" class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="s-cur" value="Kata sandi saat ini" />
                        <TextInput id="s-cur" v-model="passwordForm.current_password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                        <InputError :message="passwordForm.errors.current_password" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="s-new" value="Kata sandi baru" />
                        <TextInput id="s-new" v-model="passwordForm.password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <InputError :message="passwordForm.errors.password" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="s-conf" value="Ulangi kata sandi baru" />
                        <TextInput id="s-conf" v-model="passwordForm.password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary" :disabled="passwordForm.processing">Ubah sandi</button>
                    </div>
                </form>
            </div>

            <!-- info aplikasi -->
            <div class="panel p-6 xl:col-span-2">
                <p class="kicker">Informasi aplikasi</p>
                <h2 class="mt-1 font-display text-lg font-bold">Tentang</h2>
                <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-3">
                    <div>
                        <dt class="kicker">Aplikasi</dt>
                        <dd class="mt-1 text-sm font-medium">{{ app.name }} · v{{ app.version }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">Model prediksi</dt>
                        <dd class="mt-1 num text-sm">{{ app.model_version }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">Prediksi terakhir</dt>
                        <dd class="mt-1 num text-sm">{{ app.last_run }}</dd>
                    </div>
                </dl>
                <p class="mt-5 border-t border-line/60 pt-4 text-xs leading-relaxed text-ink-soft">
                    Model memperbarui diri tiap Minggu 06.00 WIB (lihat <code class="rounded bg-paper px-1 font-mono">schedule</code>). Prediksi
                    adalah estimasi pendukung keputusan, bukan jaminan hasil penjualan. Data transaksi berasal dari
                    backup invoice 1 Mei – 17 Okt 2026 (229 invoice).
                </p>
                <div class="mt-4 flex justify-end">
                    <button class="btn-danger" @click="logout">Keluar dari aplikasi</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
