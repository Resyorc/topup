import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

const tocItems = [
    { id: 's1', title: 'Data yang Kami Kumpulkan' },
    { id: 's2', title: 'Penggunaan Data' },
    { id: 's3', title: 'Berbagi Data dengan Pihak Ketiga' },
    { id: 's4', title: 'Keamanan Data' },
    { id: 's5', title: 'Cookies' },
    { id: 's6', title: 'Retensi Data' },
    { id: 's7', title: 'Hak-Hak Pengguna' },
    { id: 's8', title: 'Data Anak di Bawah Umur' },
    { id: 's9', title: 'Perubahan Kebijakan' },
    { id: 's10', title: 'Hubungi Kami' },
];

const sectionBase =
    'rounded-2xl border border-white/10 bg-[var(--color-bg-card)] p-5 sm:p-7 md:p-8';
const sectionHeader = 'mb-5 flex items-start gap-4';
const sectionNum =
    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-primary/40 bg-primary/15 text-sm font-black text-primary';
const sectionTitle =
    'pt-1 text-lg font-black tracking-tight text-white sm:text-xl';
const sectionText = 'mb-3 text-sm leading-7 text-gray-300 sm:text-[15px]';
const listItem =
    'relative border-b border-white/10 py-2 pl-5 text-sm text-gray-300 last:border-b-0 before:absolute before:top-2 before:left-0 before:text-primary before:content-["▸"]';
const box =
    'my-5 rounded-xl border-l-4 border-primary bg-primary/10 p-4 text-sm leading-7 text-gray-200';

export default function KebijakanPrivasiPage() {
    return (
        <GuestLayout>
            <Head title="Kebijakan Privasi" />

            <section className="relative overflow-hidden border-b border-white/10 bg-[var(--color-bg-secondary)]">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(131,39,216,0.25),transparent_45%),radial-gradient(circle_at_80%_0%,rgba(76,201,240,0.15),transparent_35%)]" />
                <div className="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
                    <span className="inline-flex rounded-full border border-primary/50 bg-primary/15 px-4 py-1 text-[11px] font-semibold tracking-[0.2em] text-primary uppercase">
                        Dokumen Resmi
                    </span>
                    <h1 className="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl">
                        Kebijakan Privasi
                    </h1>
                    <p className="mt-3 text-sm text-gray-400">
                        Berlaku sejak: 24 Maret 2026 · Versi 2.0
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div className="mb-8 rounded-2xl border border-primary/30 bg-[var(--color-bg-card)] p-5 sm:p-7">
                    <p className={sectionText}>
                        Nuvelo berkomitmen untuk melindungi privasi pengguna. Dokumen ini menjelaskan
                        data apa yang kami kumpulkan, bagaimana kami menggunakannya, dan hak-hak
                        pengguna atas data tersebut.
                    </p>
                    <h2 className="mb-4 text-base font-bold text-white">Daftar Isi</h2>
                    <ol className="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        {tocItems.map((item, idx) => (
                            <li key={item.id}>
                                <a
                                    href={`#${item.id}`}
                                    className="group inline-flex items-center gap-2 text-gray-300 transition hover:text-primary"
                                >
                                    <span className="text-xs font-semibold text-primary">
                                        {String(idx + 1).padStart(2, '0')}
                                    </span>
                                    <span>{item.title}</span>
                                </a>
                            </li>
                        ))}
                    </ol>
                </div>

                <div className="space-y-6 sm:space-y-7">
                    <section id="s1" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>01</div>
                            <h2 className={sectionTitle}>Data yang Kami Kumpulkan</h2>
                        </div>
                        <p className={sectionText + ' font-semibold text-white'}>
                            1.1 Data yang Diberikan Langsung oleh Pengguna
                        </p>
                        <ul>
                            <li className={listItem}>
                                Nama dan alamat email (saat registrasi akun)
                            </li>
                            <li className={listItem}>
                                Nomor WhatsApp (untuk keperluan CS dan notifikasi)
                            </li>
                            <li className={listItem}>
                                ID game dan nomor server (untuk memproses topup)
                            </li>
                            <li className={listItem}>Riwayat transaksi dan pesanan</li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            1.2 Data yang Dikumpulkan Otomatis
                        </p>
                        <ul>
                            <li className={listItem}>
                                Alamat IP dan informasi perangkat saat mengakses website
                            </li>
                            <li className={listItem}>
                                Halaman yang dikunjungi dan durasi kunjungan (analytics)
                            </li>
                            <li className={listItem}>
                                Cookies untuk meningkatkan pengalaman pengguna
                            </li>
                        </ul>
                        <div className={box}>
                            Nuvelo{' '}
                            <strong className="text-white">
                                tidak menyimpan data kartu kredit, nomor rekening, atau kata sandi
                                pembayaran
                            </strong>
                            . Data sensitif pembayaran diproses langsung oleh payment gateway.
                        </div>
                    </section>

                    <section id="s2" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>02</div>
                            <h2 className={sectionTitle}>Penggunaan Data</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo menggunakan data pengguna semata-mata untuk:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Memproses dan memverifikasi transaksi topup
                            </li>
                            <li className={listItem}>
                                Mengirimkan konfirmasi dan riwayat transaksi
                            </li>
                            <li className={listItem}>
                                Memberikan layanan pelanggan (CS) yang responsif
                            </li>
                            <li className={listItem}>
                                Mengirimkan informasi promo dan update layanan (dengan persetujuan
                                pengguna)
                            </li>
                            <li className={listItem}>
                                Mendeteksi dan mencegah penipuan serta penyalahgunaan layanan
                            </li>
                            <li className={listItem}>
                                Meningkatkan kualitas layanan berdasarkan data penggunaan
                            </li>
                        </ul>
                        <div className={box}>
                            Nuvelo{' '}
                            <strong className="text-white">
                                TIDAK menjual, menyewakan, atau membagikan
                            </strong>{' '}
                            data pribadi pengguna kepada pihak ketiga untuk kepentingan komersial
                            mereka.
                        </div>
                    </section>

                    <section id="s3" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>03</div>
                            <h2 className={sectionTitle}>Berbagi Data dengan Pihak Ketiga</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo hanya membagikan data pengguna kepada pihak ketiga dalam kondisi
                            berikut:
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">Penyedia payment gateway</strong>{' '}
                                untuk memproses pembayaran
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Penyedia layanan topup (Digiflazz)
                                </strong>{' '}
                                untuk memproses pengiriman produk
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Penyedia layanan analitik</strong>{' '}
                                website untuk meningkatkan performa
                            </li>
                            <li className={listItem}>
                                Apabila diwajibkan oleh hukum atau perintah pengadilan yang sah
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Seluruh pihak ketiga yang bekerja sama dengan Nuvelo terikat oleh
                            perjanjian kerahasiaan dan wajib menjaga keamanan data pengguna.
                        </p>
                    </section>

                    <section id="s4" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>04</div>
                            <h2 className={sectionTitle}>Keamanan Data</h2>
                        </div>
                        <ul>
                            <li className={listItem}>
                                Nuvelo menggunakan enkripsi{' '}
                                <strong className="text-white">SSL/TLS</strong> untuk seluruh
                                transmisi data di website
                            </li>
                            <li className={listItem}>
                                Akses ke data pengguna dibatasi hanya untuk keperluan operasional
                                yang sah
                            </li>
                            <li className={listItem}>
                                Nuvelo melakukan pemantauan rutin untuk mendeteksi ancaman keamanan
                            </li>
                            <li className={listItem}>
                                Dalam hal terjadi kebocoran data, Nuvelo akan menginformasikan
                                pengguna yang terdampak dalam waktu{' '}
                                <strong className="text-white">72 jam</strong>
                            </li>
                        </ul>
                    </section>

                    <section id="s5" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>05</div>
                            <h2 className={sectionTitle}>Cookies</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo menggunakan cookies untuk menyimpan preferensi pengguna dan
                            meningkatkan pengalaman berbelanja. Pengguna dapat mengatur atau
                            menonaktifkan cookies melalui pengaturan browser, namun hal ini dapat
                            mempengaruhi fungsi website.
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">Session cookies:</strong> dihapus
                                otomatis saat browser ditutup
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Persistent cookies:</strong> tersimpan
                                untuk mempercepat login dan menyimpan preferensi
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Analytics cookies:</strong> digunakan
                                untuk memahami pola penggunaan website secara anonim
                            </li>
                        </ul>
                    </section>

                    <section id="s6" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>06</div>
                            <h2 className={sectionTitle}>Retensi Data</h2>
                        </div>
                        <ul>
                            <li className={listItem}>
                                Data akun aktif disimpan selama akun masih aktif digunakan
                            </li>
                            <li className={listItem}>
                                Riwayat transaksi disimpan minimal{' '}
                                <strong className="text-white">2 tahun</strong> untuk keperluan
                                audit dan sengketa
                            </li>
                            <li className={listItem}>
                                Data akun yang tidak aktif selama{' '}
                                <strong className="text-white">2 tahun</strong> dapat dihapus
                                setelah notifikasi
                            </li>
                            <li className={listItem}>
                                Data yang digunakan untuk pelaporan pajak disimpan sesuai ketentuan
                                perpajakan Indonesia
                            </li>
                        </ul>
                    </section>

                    <section id="s7" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>07</div>
                            <h2 className={sectionTitle}>Hak-Hak Pengguna</h2>
                        </div>
                        <p className={sectionText}>
                            Sesuai dengan prinsip perlindungan data, pengguna Nuvelo memiliki hak:
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">Hak Akses —</strong> Meminta salinan
                                data pribadi yang kami simpan
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Hak Koreksi —</strong> Meminta
                                perbaikan data yang tidak akurat atau tidak lengkap
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Hak Penghapusan —</strong> Meminta
                                penghapusan data pribadi (kecuali data yang wajib disimpan secara
                                hukum)
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Hak Pembatasan —</strong> Membatasi
                                cara kami memproses data pribadi Anda
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Hak Portabilitas —</strong> Menerima
                                data pribadi dalam format yang dapat dibaca mesin
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Hak Keberatan —</strong> Menolak
                                pemrosesan data untuk keperluan pemasaran langsung
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Untuk menggunakan hak-hak di atas, hubungi kami melalui WhatsApp{' '}
                            <strong className="text-white">085158330663</strong>.
                        </p>
                    </section>

                    <section id="s8" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>08</div>
                            <h2 className={sectionTitle}>Data Anak di Bawah Umur</h2>
                        </div>
                        <p className={sectionText}>
                            Layanan Nuvelo tidak ditujukan untuk anak di bawah usia{' '}
                            <strong className="text-white">13 tahun</strong>. Nuvelo tidak secara
                            sengaja mengumpulkan data pribadi dari anak di bawah 13 tahun. Jika kami
                            mengetahui bahwa data tersebut telah terkumpul tanpa izin orang tua,
                            kami akan segera menghapusnya.
                        </p>
                    </section>

                    <section id="s9" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>09</div>
                            <h2 className={sectionTitle}>Perubahan Kebijakan</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo dapat memperbarui kebijakan privasi ini dari waktu ke waktu.
                            Perubahan signifikan akan diinformasikan melalui website{' '}
                            <strong className="text-white">nuvelo.id</strong> dan/atau notifikasi
                            kepada pengguna terdaftar. Tanggal pembaruan terakhir akan selalu
                            tercantum di bagian bawah dokumen ini.
                        </p>
                    </section>

                    <section id="s10" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>10</div>
                            <h2 className={sectionTitle}>Hubungi Kami</h2>
                        </div>
                        <p className={sectionText}>
                            Untuk pertanyaan, keluhan, atau permintaan terkait privasi dan data
                            pribadi Anda, hubungi:
                        </p>
                        <div className={box}>
                            <p>
                                <strong className="text-white">Nuvelo</strong>
                            </p>
                            <p>WhatsApp: 085158330663</p>
                            <p>Website: www.nuvelo.id</p>
                            <p>Instagram: @nuvelo.id</p>
                            <p>Jam Operasional: Senin–Minggu, 08.00–22.00 WIB</p>
                        </div>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Dokumen ini berlaku sejak{' '}
                            <strong className="text-white">24 Maret 2026</strong> dan menggantikan
                            seluruh versi sebelumnya.
                        </p>
                        <div className="mt-6 flex items-center gap-3">
                            <Link
                                href="/syarat-ketentuan"
                                className="rounded-md border border-primary/60 bg-primary/10 px-4 py-2 text-xs font-semibold text-primary transition hover:bg-primary/20"
                            >
                                Lihat Syarat & Ketentuan
                            </Link>
                        </div>
                    </section>
                </div>
            </section>
        </GuestLayout>
    );
}



