/**
 * Pusat pemberitahuan OSIM — disimpan per perangkat.
 * Siswa & GTK tidak punya akun, jadi status "sudah dibaca" dan preferensi
 * jenis pemberitahuan disimpan di localStorage perangkat ini saja.
 */
const KUNCI = { dibaca: 'osim-notif-dibaca', pref: 'osim-notif-pref', tampil: 'osim-notif-tampil' };

const baca = (kunci, awal) => {
    try {
        const nilai = JSON.parse(localStorage.getItem(kunci));
        return nilai ?? awal;
    } catch {
        return awal;
    }
};

const simpan = (kunci, nilai) => {
    try {
        localStorage.setItem(kunci, JSON.stringify(nilai));
    } catch {
        // Mode privat / penyimpanan penuh: tetap jalan tanpa mengingat.
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.store('notif', {
        umpan: [],
        dibaca: baca(KUNCI.dibaca, []),
        pref: { pengumuman: true, pemilihan: true, kegiatan: true, ...baca(KUNCI.pref, {}) },

        sudahDiambil: false,

        // Ambil umpan sekali per halaman penuh (navigasi wire:navigate memakai yang sudah ada).
        async ambil(alamat) {
            if (this.sudahDiambil) return;
            this.sudahDiambil = true;
            try {
                const res = await fetch(alamat, { headers: { Accept: 'application/json' } });
                if (res.ok) this.muat(await res.json());
            } catch {
                this.sudahDiambil = false; // coba lagi di halaman berikutnya
            }
        },

        muat(umpan) {
            this.umpan = umpan;
            // Bersihkan id lama yang tidak ada lagi di umpan agar penyimpanan tetap kecil.
            const ada = new Set(umpan.map((n) => n.id));
            this.dibaca = this.dibaca.filter((id) => ada.has(id));
            simpan(KUNCI.dibaca, this.dibaca);
            this.tampilkanDiPerangkat();
        },

        aktif(n) {
            return this.pref[n.jenis] !== false;
        },

        sudahDibaca(id) {
            return this.dibaca.includes(id);
        },

        get daftar() {
            return this.umpan.filter((n) => this.aktif(n));
        },

        akunBelum: 0,

        // Lonceng: umpan perangkat + pemberitahuan akun yang belum dibaca.
        get totalBelum() {
            return this.belumDibaca + this.akunBelum;
        },

        get belumDibaca() {
            return this.daftar.filter((n) => !this.sudahDibaca(n.id)).length;
        },

        tandai(id) {
            if (!this.sudahDibaca(id)) {
                this.dibaca = [...this.dibaca, id];
                simpan(KUNCI.dibaca, this.dibaca);
            }
        },

        tandaiSemua() {
            this.dibaca = [...new Set([...this.dibaca, ...this.daftar.map((n) => n.id)])];
            simpan(KUNCI.dibaca, this.dibaca);
        },

        ubahPref(jenis, nilai) {
            this.pref = { ...this.pref, [jenis]: nilai };
            simpan(KUNCI.pref, this.pref);
        },

        // Pemberitahuan sistem (selama situs terbuka) bila pengguna mengizinkan.
        get izinPerangkat() {
            return 'Notification' in window ? Notification.permission : 'tidak-didukung';
        },

        async mintaIzin() {
            if (!('Notification' in window)) return;
            await Notification.requestPermission();
            this.pref = { ...this.pref }; // picu tampilan ulang status izin
            this.tampilkanDiPerangkat();
        },

        tampilkanDiPerangkat() {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            const sudah = new Set(baca(KUNCI.tampil, []));
            this.daftar
                .filter((n) => !this.sudahDibaca(n.id) && !sudah.has(n.id))
                .slice(0, 3)
                .forEach((n) => {
                    const p = new Notification(n.judul, { body: n.isi, icon: '/favicon.png', tag: n.id });
                    p.onclick = () => { window.focus(); this.tandai(n.id); window.location.href = n.tautan; };
                    sudah.add(n.id);
                });
            simpan(KUNCI.tampil, [...sudah].slice(-100));
        },
    });
});
