# Product Requirements Document (PRD)

## Website Prediksi Penjualan IDE Segar Printing

**Versi:** 1.0\
**Tanggal:** 26 Oktober 2026\
**Nama produk:** IDE Segar Printing --- Sales Prediction Dashboard\
**Jenis aplikasi:** Website dashboard administrasi untuk usaha
percetakan

------------------------------------------------------------------------

## 1. Ringkasan Produk

IDE Segar Printing --- Sales Prediction Dashboard adalah website
internal untuk membantu usaha percetakan **IDE Segar Printing** memantau
operasional penjualan, memahami tren permintaan, dan memperkirakan
penjualan pada periode mendatang. Website menyajikan ringkasan metrik,
grafik perbandingan penjualan aktual dengan prediksi, distribusi
kategori produk, serta daftar produk dengan potensi penjualan tertinggi.

Prediksi digunakan sebagai bahan pendukung pengambilan keputusan,
terutama untuk merencanakan persediaan bahan, menentukan prioritas
produksi, dan mengenali produk yang diperkirakan mengalami peningkatan
permintaan. Nilai yang terlihat pada desain awal merupakan **contoh data
visual** dan bukan data bisnis nyata; implementasi harus mengambil angka
dari basis data dan hasil perhitungan model.

## 2. Latar Belakang dan Masalah

Usaha percetakan menangani produk dengan karakteristik permintaan yang
beragam, seperti banner, kartu nama, brosur, cetak digital, dan produk
offset. Tanpa ringkasan data yang mudah dibaca, pengelola dapat
kesulitan untuk: - Memahami perkembangan penjualan aktual. -
Memperkirakan jumlah pesanan dan pendapatan pada periode berikutnya. -
Mengetahui produk yang berpotensi tumbuh dan membutuhkan tambahan stok
bahan. - Membandingkan kontribusi kategori produk terhadap penjualan. -
Menentukan prioritas tindak lanjut berdasarkan data.

Website ini menyediakan satu dashboard terpusat untuk menyajikan
informasi tersebut secara ringkas dan dapat ditelusuri melalui menu
operasional.

## 3. Tujuan Produk

1.  Menampilkan ringkasan performa penjualan dan hasil prediksi dalam
    satu dashboard.
2.  Membantu memperkirakan pendapatan, jumlah pesanan, dan kebutuhan
    persediaan pada periode mendatang.
3.  Menampilkan tren penjualan aktual dibandingkan hasil prediksi.
4.  Mengidentifikasi kategori dan produk yang berpotensi memiliki
    permintaan tinggi.
5.  Menyediakan menu untuk mengelola produk, pelanggan, pesanan, dan
    laporan.
6.  Menyediakan rekomendasi yang dapat membantu pengelola menentukan
    prioritas stok dan produksi.

### 3.1 Indikator Keberhasilan

-   Pengguna dapat melihat ringkasan penjualan tanpa menghitung data
    secara manual.
-   Data dashboard konsisten dengan data transaksi yang tersimpan.
-   Prediksi menampilkan periode, satuan, dan waktu pembaruan yang
    jelas.
-   Pengguna dapat menemukan produk prioritas dan menelusuri informasi
    pendukungnya.
-   Laporan dapat difilter sesuai periode yang dipilih dan diekspor
    apabila fitur ekspor diimplementasikan.

## 4. Pengguna dan Hak Akses

### 4.1 Admin/Pengelola

Pengguna utama yang masuk ke dashboard untuk memantau hasil prediksi dan
mengelola data operasional.

Hak akses yang direncanakan: - Melihat dashboard prediksi penjualan. -
Mengelola data produk. - Melihat dan mengelola pesanan. - Melihat dan
mengelola data pelanggan. - Membuka laporan penjualan dan prediksi. -
Melihat notifikasi dan rekomendasi. - Mengakses pengaturan akun dan
aplikasi.

### 4.2 Autentikasi

Pada bagian atas desain terdapat opsi **Sign up with email** dan
**Continue with Google**. Jika autentikasi tersebut digunakan pada
produk akhir, sistem perlu mendukung: - Pendaftaran akun dengan email. -
Masuk dengan email dan kata sandi. - Masuk menggunakan akun Google. -
Validasi input dan penanganan kegagalan login. - Logout dan pengelolaan
sesi. - Pembatasan halaman dashboard untuk pengguna yang belum
terautentikasi.

Metode autentikasi dan kebijakan pendaftaran pengguna perlu dikonfirmasi
saat implementasi. Jika website hanya digunakan secara internal,
pendaftaran mandiri dapat diganti dengan pembuatan akun oleh admin.

## 5. Struktur Navigasi dan Menu

Sidebar pada desain memuat menu berikut:

1.  **Dashboard**
2.  **Prediksi Penjualan**
3.  **Pesanan**
4.  **Produk**
5.  **Pelanggan**
6.  **Laporan**
7.  **Pengaturan**

Di bagian bawah sidebar terdapat identitas pengguna yang sedang masuk.
Header atas menampilkan judul halaman, pencarian produk, notifikasi, dan
elemen akun.

> Catatan: tampilan yang diberikan memperlihatkan halaman **Prediksi
> Penjualan** aktif. Detail perilaku halaman lain pada dokumen ini
> merupakan kebutuhan fungsional yang diusulkan berdasarkan nama menu
> dan konteks bisnis; detail tersebut perlu disesuaikan dengan proses
> kerja IDE Segar Printing.

## 6. Kebutuhan Fungsional

### 6.1 Dashboard

**Tujuan:** Memberikan gambaran umum kinerja usaha dalam satu tampilan.

Fitur: - Menampilkan ringkasan metrik penjualan dan pesanan. -
Menampilkan tren performa untuk periode yang dipilih. - Menampilkan
produk atau kategori dengan kontribusi terbesar. - Menampilkan
notifikasi atau rekomendasi penting. - Menyediakan tautan menuju halaman
detail terkait.

Kriteria penerimaan: - Data dashboard berasal dari sumber data yang sama
dengan halaman transaksi dan laporan. - Setiap metrik memiliki nama,
satuan, dan periode yang jelas. - Kondisi data kosong ditampilkan dengan
pesan yang informatif, bukan angka yang menyesatkan.

### 6.2 Prediksi Penjualan

**Tujuan:** Menampilkan hasil estimasi penjualan untuk membantu
perencanaan usaha.

#### A. Banner Insight Mingguan

Banner berwarna ungu menampilkan insight utama minggu ini. Pada contoh
desain, insight berbunyi bahwa permintaan banner diprediksi meningkat
menjelang musim promosi.

Fitur: - Menampilkan satu insight atau rekomendasi utama. - Menyebutkan
produk/kategori terkait, perkiraan perubahan, dan konteks periode jika
tersedia. - Menampilkan tombol **Lihat rekomendasi** untuk membuka
rincian rekomendasi. - Menyediakan fallback apabila belum ada insight
yang dapat dihasilkan.

#### B. Kartu Ringkasan Prediksi

Desain memuat empat kartu ringkasan:

1.  **Prediksi Pendapatan**
    -   Menampilkan estimasi pendapatan untuk periode mendatang.
    -   Format mata uang Rupiah.
    -   Menampilkan perubahan persentase terhadap periode pembanding.
2.  **Prediksi Pesanan**
    -   Menampilkan estimasi jumlah pesanan pada periode mendatang.
    -   Menampilkan perubahan persentase terhadap periode pembanding.
3.  **Akurasi Model**
    -   Menampilkan metrik evaluasi model berdasarkan data historis.
    -   Menampilkan perubahan dibandingkan periode evaluasi sebelumnya
        jika tersedia.
    -   Nama metrik, rentang nilai, dan metode perhitungan harus
        dijelaskan pada tooltip atau halaman detail.
4.  **Produk Bertumbuh**
    -   Menampilkan jumlah produk yang diperkirakan mengalami
        pertumbuhan.
    -   Menampilkan jumlah produk yang perlu diprioritaskan atau
        diperiksa stoknya.
    -   Klik kartu dapat membuka daftar produk terkait.

Catatan implementasi: - Persentase kenaikan atau penurunan harus
dihitung dari data pembanding yang valid. - Jika data pembanding
bernilai nol atau tidak tersedia, sistem harus menampilkan keterangan
yang sesuai dan tidak membagi dengan nol. - Akurasi model tidak boleh
berupa angka statis. Angka harus berasal dari evaluasi model yang
benar-benar dijalankan.

#### C. Grafik Proyeksi Penjualan

Grafik membandingkan **Prediksi** dan **Aktual**.

Fitur: - Menampilkan garis prediksi dan garis penjualan aktual. -
Menyediakan pilihan periode **7 Hari**, **30 Hari**, dan **3 Bulan**. -
Sumbu waktu menampilkan tanggal atau label periode. - Tooltip
menampilkan tanggal/periode dan nilai setiap seri. - Legenda membedakan
prediksi dan aktual. - Grafik diperbarui saat periode pilihan berubah. -
Jika data aktual untuk tanggal mendatang belum tersedia, bagian tersebut
tidak boleh direpresentasikan sebagai data aktual. - Menampilkan status
pemuatan, kondisi data kosong, dan pesan kesalahan jika data gagal
dimuat.

#### D. Distribusi Kategori

Grafik donat menunjukkan estimasi kontribusi penjualan berdasarkan
kategori produk.

Contoh kategori pada desain: - Large Format - Digital Print -
Merchandise - Offset

Fitur: - Menampilkan persentase kontribusi setiap kategori. -
Menampilkan total 100% di tengah grafik apabila seluruh kontribusi dapat
dihitung. - Menampilkan legenda kategori dan persentasenya. - Warna
kategori konsisten antara grafik dan legenda. - Data dihitung
berdasarkan definisi kontribusi yang ditetapkan, misalnya pendapatan
atau volume penjualan; definisi tersebut harus terlihat pada keterangan
grafik.

#### E. Produk dengan Potensi Tertinggi

Tabel rekomendasi produk berdasarkan tren historis. Desain menampilkan
kolom:

  Kolom         Keterangan
  ------------- --------------------------------------------------
  Produk        Nama, kode, dan ikon/inisial produk
  Terjual       Jumlah penjualan aktual pada periode acuan
  Prediksi      Estimasi jumlah penjualan pada periode prediksi
  Pertumbuhan   Persentase perubahan terhadap periode pembanding
  Status        Label tindak lanjut, misalnya "Prioritas stok"

Fitur: - Menampilkan daftar produk dengan potensi tertinggi. -
Mengurutkan daftar berdasarkan aturan yang ditetapkan, misalnya
pertumbuhan prediksi atau skor potensi. - Menampilkan satuan yang sesuai
dengan produk, seperti pcs atau m². - Tombol **Lihat semua** membuka
daftar lengkap produk berpotensi tinggi. - Klik baris atau nama produk
dapat membuka detail produk dan data prediksinya. - Label prioritas
ditentukan oleh aturan yang terdokumentasi, bukan ditetapkan secara
manual tanpa alasan. - Jika prediksi menyarankan peningkatan stok,
sistem sebaiknya mempertimbangkan stok saat ini dan waktu pengadaan
bahan sebelum memberi rekomendasi.

### 6.3 Pencarian Produk Global

Kolom pencarian di header menampilkan placeholder **Cari produk...**.

Fitur: - Pencarian berdasarkan nama produk atau kode produk. -
Menampilkan hasil yang cocok saat pengguna mengetik. - Mendukung
pemilihan hasil untuk membuka detail produk. - Menampilkan kondisi tidak
ada hasil. - Menyediakan indikator pemuatan bila pencarian membutuhkan
waktu. - Membatasi hasil yang ditampilkan agar dropdown tetap mudah
dibaca. - Pencarian tidak boleh menampilkan data yang tidak boleh
diakses oleh pengguna.

### 6.4 Notifikasi

Ikon lonceng di header menyediakan akses ke notifikasi.

Notifikasi yang diusulkan: - Produk diprediksi mengalami lonjakan
permintaan. - Stok produk atau bahan diperkirakan tidak mencukupi. - Ada
pesanan baru atau perubahan status pesanan. - Prediksi terbaru berhasil
dibuat atau gagal diproses. - Ada data penting yang belum lengkap.

Fitur: - Menampilkan indikator jumlah notifikasi belum dibaca. -
Pengguna dapat membuka detail notifikasi. - Notifikasi dapat ditandai
telah dibaca. - Notifikasi mengarah ke halaman terkait jika relevan. -
Notifikasi harus dibuat berdasarkan peristiwa atau aturan yang jelas.

### 6.5 Pesanan

**Tujuan:** Memantau dan mengelola pesanan pelanggan.

Kebutuhan dasar yang diusulkan: - Menampilkan daftar pesanan. - Mencari
pesanan berdasarkan nomor pesanan atau nama pelanggan. - Memfilter
berdasarkan status dan rentang tanggal. - Melihat detail pesanan, item
produk, jumlah, harga, total, dan informasi pelanggan. - Mencatat atau
memperbarui status pesanan sesuai alur kerja usaha. - Melihat tanggal
pesanan dan status pembayaran jika informasi tersebut dikelola sistem. -
Menampilkan badge jumlah pesanan yang membutuhkan perhatian pada
sidebar, seperti angka "12" pada desain contoh.

Status pesanan perlu ditetapkan sesuai proses aktual IDE Segar Printing.
Contoh alur yang dapat dipertimbangkan: **Menunggu konfirmasi → Diproses
→ Siap diambil/Dikirim → Selesai**, dengan status pembatalan jika
diperlukan. Status pembayaran sebaiknya dipisahkan dari status
pengerjaan.

### 6.6 Produk

**Tujuan:** Mengelola katalog produk percetakan yang menjadi sumber data
prediksi.

Kebutuhan dasar yang diusulkan: - Menampilkan daftar produk. - Menambah,
melihat, mengubah, dan menonaktifkan produk. - Menyimpan nama, kode,
kategori, deskripsi, satuan, harga, dan status produk. - Menyimpan data
stok produk atau kebutuhan bahan jika proses inventaris termasuk cakupan
aplikasi. - Mencari dan memfilter produk berdasarkan kategori dan
status. - Menampilkan produk yang tersedia dan tidak tersedia. -
Menyediakan detail histori penjualan dan hasil prediksi untuk setiap
produk.

Contoh produk pada desain: - Banner Flexi 280gr (kode contoh:
BNR-280). - Kartu Nama Art Carton (kode contoh: CRD-260). - Brosur A5
(kode contoh: BRS-A5).

Kode dan angka tersebut adalah contoh dari desain dan perlu diganti atau
diverifikasi terhadap data nyata.

### 6.7 Pelanggan

**Tujuan:** Menyimpan informasi pelanggan dan memudahkan penelusuran
riwayat transaksi.

Kebutuhan dasar yang diusulkan: - Menampilkan daftar pelanggan. -
Menambah, melihat, dan memperbarui data pelanggan. - Mencari pelanggan
berdasarkan nama atau informasi kontak yang diizinkan. - Melihat riwayat
pesanan per pelanggan. - Menyimpan nama, nomor kontak, dan informasi
lain yang memang diperlukan untuk layanan. - Memvalidasi format data dan
mencegah duplikasi yang tidak diinginkan. - Membatasi akses dan
penggunaan data pribadi hanya untuk kebutuhan operasional.

### 6.8 Laporan

**Tujuan:** Menyediakan laporan historis dan prediksi untuk evaluasi
bisnis.

Jenis laporan yang diusulkan: - Laporan penjualan berdasarkan periode. -
Laporan jumlah pesanan. - Laporan penjualan berdasarkan produk. -
Laporan kontribusi kategori. - Laporan perbandingan aktual dan
prediksi. - Laporan produk dengan pertumbuhan tertinggi. - Laporan
evaluasi performa model prediksi.

Fitur: - Filter tanggal/periode, produk, kategori, dan status yang
relevan. - Ringkasan total dan tabel rincian. - Grafik tren jika
membantu interpretasi. - Opsi ekspor CSV/Excel atau PDF sebagai fitur
yang perlu dikonfirmasi dalam ruang lingkup implementasi. - Menampilkan
waktu pembuatan laporan dan filter yang digunakan. - Menjaga konsistensi
angka dengan dashboard.

### 6.9 Pengaturan

**Tujuan:** Mengelola preferensi aplikasi dan akun.

Kebutuhan dasar yang diusulkan: - Melihat dan memperbarui profil
pengguna. - Mengubah kata sandi atau mengelola metode login jika
didukung. - Mengatur preferensi notifikasi. - Mengatur preferensi
tampilan atau format data yang relevan. - Mengelola kategori produk dan
satuan apabila hal tersebut tidak ditempatkan pada menu khusus. -
Menampilkan informasi aplikasi dan opsi logout.

Pengaturan teknis model prediksi---seperti jadwal pelatihan, rentang
data historis, atau pemilihan algoritma---dapat ditempatkan pada bagian
pengaturan lanjutan jika memang dibutuhkan. Perubahan model sebaiknya
dibatasi kepada pengguna yang berwenang.

### 6.10 Tombol Rekomendasi

Tombol **Lihat rekomendasi** pada banner membuka penjelasan rekomendasi
yang dapat ditindaklanjuti, misalnya: - Produk yang diperkirakan
meningkat permintaannya. - Besaran perubahan dan periode prediksi. -
Dasar rekomendasi yang dapat dipahami pengguna. - Perkiraan kebutuhan
stok atau bahan, jika data stok tersedia. - Saran prioritas dan catatan
risiko.

Rekomendasi harus membedakan antara hasil model dan saran operasional.
Sistem tidak boleh menyatakan bahwa prediksi pasti terjadi.

### 6.11 Elemen Identitas dan Bantuan

Pada bagian atas desain terdapat judul **Admin Percetakan Prediksi
Penjualan**, ikon bantuan, serta pilihan autentikasi. Sidebar memuat
nama produk **Printora** dan label **Admin Dashboard**, sementara
kebutuhan bisnis menyebut nama usaha **IDE Segar Printing**.

Keputusan branding: - Nama usaha yang ditampilkan kepada pengguna harus
konsisten menggunakan **IDE Segar Printing**, kecuali "Printora" memang
akan dipertahankan sebagai nama aplikasi. - Ikon bantuan dapat membuka
panduan singkat atau informasi penggunaan. - Informasi akun aktif
ditampilkan di bagian bawah sidebar. - Elemen eksternal seperti tautan
kebijakan privasi atau pengaturan cookie hanya ditampilkan jika memang
diterapkan.

## 7. Kebutuhan Data

### 7.1 Data Transaksi

Minimal mencakup: - ID/nomor pesanan. - Tanggal pesanan. - ID pelanggan
atau informasi pelanggan yang diperlukan. - Daftar item produk. - Jumlah
per item dan satuan. - Harga satuan, diskon, dan total transaksi jika
berlaku. - Status pesanan dan status pembayaran jika dikelola. - Tanggal
penyelesaian atau pengiriman jika relevan.

### 7.2 Data Produk

Minimal mencakup: - ID dan kode produk. - Nama produk. - Kategori. -
Satuan penjualan. - Harga. - Status aktif/ketersediaan. - Stok atau
informasi bahan, apabila manajemen persediaan termasuk cakupan. -
Riwayat perubahan data yang relevan untuk menjaga kualitas analisis.

### 7.3 Data Pelanggan

Minimal mencakup ID pelanggan, nama, dan kontak yang diperlukan untuk
operasional. Data pribadi harus dibatasi sesuai prinsip minimisasi data.

### 7.4 Data Prediksi

Setiap hasil prediksi idealnya menyimpan: - Produk/kategori yang
diprediksi. - Waktu prediksi dibuat. - Periode target. - Nilai prediksi
dan satuannya. - Nilai aktual ketika tersedia. - Versi model atau
konfigurasi yang digunakan. - Metrik evaluasi model dan rentang data
evaluasinya. - Status proses prediksi dan pesan kesalahan jika gagal.

### 7.5 Kualitas Data

-   Data tanggal, harga, kuantitas, kategori, dan satuan harus
    tervalidasi.
-   Transaksi batal atau data duplikat harus ditangani secara konsisten.
-   Data kosong tidak boleh otomatis dianggap nol tanpa aturan bisnis.
-   Data historis harus cukup dan relevan sebelum model menghasilkan
    prediksi.
-   Perubahan definisi produk, kategori, atau satuan harus
    diperhitungkan agar tidak merusak perbandingan historis.

## 8. Logika Prediksi dan Rekomendasi

Algoritma prediksi belum ditentukan oleh desain visual. Pemilihan model
perlu mengikuti karakteristik data aktual dan diuji dengan data
historis.

Alur yang disarankan: 1. Mengambil dan memvalidasi data transaksi
historis. 2. Membersihkan data duplikat, tidak lengkap, atau tidak valid
berdasarkan aturan bisnis. 3. Mengagregasi penjualan per periode,
produk, atau kategori. 4. Membagi data untuk pelatihan dan evaluasi
dengan memperhatikan urutan waktu. 5. Melatih atau menjalankan model
prediksi yang dipilih. 6. Menghasilkan estimasi untuk horizon yang
ditentukan. 7. Menghitung metrik evaluasi yang sesuai, misalnya MAE,
RMSE, atau MAPE jika karakteristik datanya memungkinkan. 8.
Membandingkan prediksi dengan data aktual ketika data aktual sudah
tersedia. 9. Menghasilkan rekomendasi berdasarkan hasil prediksi, stok,
dan aturan bisnis yang disepakati. 10. Menyimpan hasil, waktu pembaruan,
dan versi model agar hasil dapat ditelusuri.

**Ketentuan penting:** - Akurasi model bukan sekadar persentase yang
ditentukan secara manual. Metrik dan cara interpretasinya harus
dijelaskan. - MAPE dapat bermasalah ketika nilai aktual nol atau
mendekati nol; pilih metrik yang sesuai dengan data. - Prediksi tidak
menjamin hasil penjualan. Tampilkan label "estimasi" atau "prediksi". -
Hindari kebocoran data masa depan saat melatih atau mengevaluasi
model. - Perubahan musim, promosi, harga, kapasitas produksi, dan
ketersediaan bahan dapat memengaruhi hasil. - Rekomendasi stok harus
mempertimbangkan waktu produksi/pengadaan dan stok aktual, jika data
tersebut tersedia.

## 9. Kebutuhan Antarmuka dan Pengalaman Pengguna

### 9.1 Tata Letak

-   Sidebar tetap di sisi kiri pada layar desktop.
-   Header halaman berada di bagian atas area konten.
-   Area utama menggunakan latar abu-abu sangat muda.
-   Kartu informasi menggunakan latar putih, sudut membulat, dan
    bayangan/batas halus.
-   Warna aksen utama ungu, dengan warna pendukung hijau, oranye, dan
    biru untuk membedakan kategori informasi.
-   Grafik dan tabel disajikan di dalam panel terpisah.
-   Menu aktif ditandai dengan latar atau warna aksen.
-   Label status menggunakan teks yang jelas, bukan warna saja.

### 9.2 Responsivitas

-   Pada desktop, sidebar dan konten tampil berdampingan.
-   Pada tablet, ukuran kartu dan jarak antarelemen menyesuaikan lebar
    layar.
-   Pada ponsel, sidebar dapat berubah menjadi menu drawer atau navigasi
    ringkas.
-   Tabel harus dapat digulir horizontal atau berubah menjadi tampilan
    kartu pada layar sempit.
-   Grafik harus tetap memiliki tooltip dan label yang terbaca pada
    layar kecil.

### 9.3 Aksesibilitas

-   Kontras teks dan latar harus memadai.
-   Ikon interaktif memiliki label aksesibel atau tooltip.
-   Semua fungsi utama dapat diakses menggunakan keyboard.
-   Fokus keyboard terlihat dengan jelas.
-   Warna bukan satu-satunya cara untuk membedakan status atau seri
    grafik.
-   Formulir memiliki label, pesan validasi, dan petunjuk kesalahan yang
    jelas.

### 9.4 Status Antarmuka

Setiap halaman yang memuat data harus memiliki kondisi: - Memuat data. -
Data tersedia. - Data kosong. - Terjadi kesalahan. - Aksi berhasil. -
Aksi gagal dan cara untuk mencoba kembali.

## 10. Kebutuhan Nonfungsional

### 10.1 Keamanan

-   Autentikasi dan otorisasi pada setiap halaman yang memerlukan akses
    internal.
-   Validasi input di sisi server.
-   Proteksi terhadap serangan umum seperti SQL injection, XSS, dan CSRF
    sesuai teknologi yang digunakan.
-   Kata sandi disimpan menggunakan hashing yang aman.
-   Rahasia aplikasi dan kredensial OAuth tidak disimpan dalam kode
    publik.
-   Pembatasan percobaan login dan pengelolaan sesi yang aman.
-   Pencatatan aktivitas penting untuk tindakan yang berdampak pada
    data.

### 10.2 Kinerja

-   Dashboard memuat metrik utama secara efisien.
-   Query data menggunakan indeks yang sesuai dan menghindari pemuatan
    data berlebihan.
-   Proses prediksi yang berat dapat dijalankan melalui job terjadwal
    atau antrean.
-   Tabel menggunakan pagination ketika data bertambah banyak.
-   Grafik tidak memuat seluruh transaksi mentah apabila agregasi sudah
    mencukupi.

### 10.3 Keandalan

-   Kegagalan proses prediksi tidak boleh menghapus hasil prediksi
    terakhir yang masih valid.
-   Sistem memberi tahu pengguna kapan prediksi terakhir berhasil
    diperbarui.
-   Backup dan pemulihan basis data perlu direncanakan.
-   Operasi penting dan perubahan data harus dapat ditelusuri.

### 10.4 Privasi

-   Informasi pelanggan hanya digunakan untuk kebutuhan bisnis yang sah.
-   Akses terhadap data pelanggan dibatasi sesuai peran.
-   Kebijakan penyimpanan dan penghapusan data perlu ditentukan oleh
    pemilik usaha.
-   Fitur login pihak ketiga dan analitik harus mengikuti kebijakan
    privasi yang berlaku.

## 11. Entitas Data yang Disarankan

Rancangan awal basis data dapat mencakup entitas berikut: - **users**:
akun admin/pengelola dan informasi autentikasi. - **customers**: data
pelanggan. - **products**: katalog produk, kategori, satuan, harga, dan
status. - **categories**: kategori produk seperti Large Format, Digital
Print, Merchandise, dan Offset. - **orders**: informasi utama pesanan. -
**order_items**: detail produk dan kuantitas per pesanan. -
**payments**: informasi pembayaran jika pencatatan pembayaran termasuk
ruang lingkup. - **inventory** atau **materials**: stok produk/bahan
jika manajemen persediaan diterapkan. - **sales_aggregates**: agregasi
penjualan per periode untuk analisis, jika diperlukan. -
**prediction_runs**: catatan waktu dan status proses prediksi. -
**sales_predictions**: nilai prediksi per produk/kategori dan periode
target. - **model_evaluations**: metrik evaluasi dan rincian periode
evaluasi. - **notifications**: notifikasi untuk pengguna. -
**activity_logs**: catatan aktivitas penting bila dibutuhkan.

Nama tabel di atas merupakan usulan konseptual dan tidak mewajibkan
implementasi dengan nama yang persis sama.

## 12. Alur Penggunaan Utama

### 12.1 Memantau Prediksi

1.  Admin masuk ke website.
2.  Admin membuka menu Prediksi Penjualan.
3.  Sistem menampilkan kartu ringkasan dan waktu pembaruan data.
4.  Admin memilih periode 7 Hari, 30 Hari, atau 3 Bulan.
5.  Grafik dan tabel diperbarui sesuai periode.
6.  Admin memilih produk yang berpotensi tinggi untuk melihat detail.
7.  Admin menggunakan hasil tersebut sebagai bahan pertimbangan untuk
    stok dan produksi.

### 12.2 Menelusuri Rekomendasi

1.  Admin melihat banner insight mingguan.
2.  Admin memilih **Lihat rekomendasi**.
3.  Sistem menampilkan produk, periode, nilai prediksi, perubahan yang
    diperkirakan, serta dasar rekomendasi.
4.  Admin memeriksa stok dan kapasitas produksi jika data tersebut
    tersedia.
5.  Admin menentukan tindak lanjut operasional.

### 12.3 Mengelola Produk

1.  Admin membuka menu Produk.
2.  Admin mencari atau memfilter produk.
3.  Admin membuka detail produk atau menambahkan/mengubah data.
4.  Sistem memvalidasi data.
5.  Sistem menyimpan perubahan dan menampilkan konfirmasi.
6.  Perubahan yang memengaruhi prediksi diperhitungkan dalam proses
    prediksi berikutnya.

## 13. Kriteria Penerimaan Umum

Produk dapat dianggap memenuhi kebutuhan dasar jika: - Pengguna yang
tidak terautentikasi tidak dapat membuka halaman internal. - Semua menu
sidebar membuka halaman yang benar. - Ringkasan dashboard dihitung dari
data yang dapat ditelusuri. - Filter 7 Hari, 30 Hari, dan 3 Bulan
mengubah periode grafik secara benar. - Grafik membedakan data aktual
dari data prediksi. - Distribusi kategori berjumlah 100% jika semua
kategori yang relevan tercakup dan perhitungan valid. - Tabel produk
menampilkan nama, nilai aktual, nilai prediksi, pertumbuhan, dan status
yang konsisten. - Pencarian produk menampilkan hasil relevan atau
kondisi tidak ditemukan. - Fitur pesanan, produk, pelanggan, dan laporan
memiliki validasi serta kondisi kosong/kesalahan. - Akurasi model
ditampilkan berdasarkan evaluasi yang terdokumentasi. - Sistem tidak
mengarang nilai ketika data belum tersedia. - Tampilan tetap dapat
digunakan pada desktop, tablet, dan ponsel.

## 14. Ruang Lingkup dan Prioritas Implementasi

### Prioritas 1 --- MVP

-   Autentikasi admin.
-   Layout dashboard dan sidebar.
-   Ringkasan metrik penjualan.
-   Halaman Prediksi Penjualan.
-   Grafik aktual vs prediksi dengan filter periode.
-   Distribusi kategori.
-   Tabel produk dengan potensi tertinggi.
-   CRUD produk dan kategori.
-   Daftar pesanan dan detail pesanan.
-   Pencarian produk.
-   Validasi, loading state, empty state, dan error state.

### Prioritas 2 --- Pengembangan Lanjutan

-   Pengelolaan pelanggan yang lebih lengkap.
-   Laporan dengan filter dan ekspor.
-   Notifikasi berbasis peristiwa.
-   Rekomendasi stok yang mempertimbangkan stok dan waktu pengadaan.
-   Riwayat proses prediksi dan evaluasi model.
-   Login Google jika diperlukan.
-   Log aktivitas admin.

### Prioritas 3 --- Pengembangan Opsional

-   Pengaturan lanjutan model prediksi.
-   Prediksi berdasarkan musim, promosi, atau variabel eksternal jika
    data tersedia.
-   Peringatan otomatis untuk risiko kekurangan stok.
-   Integrasi dengan sistem kasir atau aplikasi transaksi yang sudah
    digunakan.
-   Hak akses berdasarkan peran jika jumlah pengguna bertambah.

## 15. Hal yang Perlu Dikonfirmasi

Sebelum implementasi final, pemilik usaha perlu memastikan: 1. Sumber
data transaksi berasal dari input manual, file Excel, aplikasi kasir,
atau sistem lain. 2. Apakah website hanya untuk admin internal atau juga
menyediakan akun bagi pengguna lain. 3. Definisi pendapatan: apakah
menggunakan nilai pesanan bruto, nilai setelah diskon, atau pendapatan
yang sudah dibayar. 4. Satuan prediksi yang dibutuhkan untuk setiap
produk, misalnya pcs, lembar, atau m². 5. Horizon prediksi dan seberapa
sering prediksi diperbarui. 6. Algoritma dan metrik evaluasi yang cocok
setelah data historis diperiksa. 7. Apakah stok produk dan bahan baku
dikelola di website ini. 8. Status pesanan yang sesuai dengan alur kerja
nyata. 9. Format ekspor laporan yang dibutuhkan. 10. Apakah nama
aplikasi yang ditampilkan adalah **IDE Segar Printing** atau nama produk
terpisah seperti "Printora".

## 16. Asumsi dan Batasan

-   Tampilan yang diberikan menjadi acuan visual untuk halaman Prediksi
    Penjualan.
-   Angka seperti pendapatan Rp186,4 juta, 1.842 pesanan, akurasi 92,6%,
    persentase pertumbuhan, dan jumlah produk adalah **data ilustratif
    pada mockup**, bukan fakta tentang usaha.
-   Kategori dan nama produk yang ditampilkan merupakan contoh yang
    perlu diverifikasi.
-   Desain tidak menentukan algoritma prediksi, skema basis data, atau
    sumber transaksi; semuanya perlu diputuskan saat analisis teknis.
-   Prediksi adalah alat bantu keputusan, bukan jaminan pendapatan atau
    permintaan.
-   Detail halaman menu selain Prediksi Penjualan dikembangkan sebagai
    usulan fungsional berdasarkan label menu pada gambar.

------------------------------------------------------------------------

**Dokumen ini menjadi acuan awal kebutuhan produk (PRD) untuk
pengembangan website prediksi penjualan IDE Segar Printing.** Detail
yang belum ditetapkan perlu dikonfirmasi sebelum dijadikan spesifikasi
implementasi final.
