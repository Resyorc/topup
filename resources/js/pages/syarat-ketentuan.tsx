import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

const tocItems = [
    { id: 's1', title: 'Tentang Nuvelo' },
    { id: 's2', title: 'Ketentuan Pengguna' },
    { id: 's3', title: 'Layanan & Produk' },
    { id: 's4', title: 'Pembayaran' },
    { id: 's5', title: 'Kebijakan Refund' },
    { id: 's6', title: 'Program Loyalitas (Krysta Coin)' },
    { id: 's7', title: 'Kode Promo & Diskon' },
    { id: 's8', title: 'Batasan Tanggung Jawab' },
    { id: 's9', title: 'Perubahan Ketentuan' },
    { id: 's10', title: 'Hukum yang Berlaku' },
    { id: 's11', title: 'Hubungi Kami' },
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
const warning =
    'my-5 rounded-xl border-l-4 border-red-500 bg-red-500/10 p-4 text-sm leading-7 text-red-200';

export default function SyaratKetentuanPage() {
    return (
        <GuestLayout>
            <Head title="Syarat & Ketentuan" />

            <section className="relative overflow-hidden border-b border-white/10 bg-[var(--color-bg-secondary)]">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(131,39,216,0.25),transparent_45%),radial-gradient(circle_at_80%_0%,rgba(76,201,240,0.15),transparent_35%)]" />
                <div className="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
                    <span className="inline-flex rounded-full border border-primary/50 bg-primary/15 px-4 py-1 text-[11px] font-semibold tracking-[0.2em] text-primary uppercase">
                        Dokumen Resmi
                    </span>
                    <h1 className="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl">
                        Syarat & Ketentuan
                    </h1>
                    <p className="mt-3 text-sm text-gray-400">
                        Berlaku sejak: 24 Maret 2026 · Versi 2.0
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div className="mb-8 rounded-2xl border border-primary/30 bg-[var(--color-bg-card)] p-5 sm:p-7">
                    <p className={sectionText}>
                        Dengan menggunakan layanan Nuvelo, Anda dianggap telah membaca, memahami,
                        dan menyetujui seluruh syarat & ketentuan yang berlaku di bawah ini.
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
                            <h2 className={sectionTitle}>Tentang Nuvelo</h2>
                        </div>
                        <p className={sectionText}>
                            <strong className="text-white">Nuvelo</strong> adalah platform layanan
                            topup game dan voucher digital yang dioperasikan secara perorangan dan
                            berdomisili di Indonesia. Nuvelo menyediakan layanan pembelian kredit
                            game, voucher digital, dan produk digital lainnya melalui website{' '}
                            <strong className="text-white">www.nuvelo.id</strong>.
                        </p>
                        <p className={sectionText}>
                            Nuvelo berperan sebagai reseller produk digital yang sumber produknya
                            berasal dari penyedia layanan terpercaya. Seluruh transaksi bersifat
                            final setelah produk berhasil dikirimkan ke akun tujuan pengguna.
                        </p>
                    </section>

                    <section id="s2" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>02</div>
                            <h2 className={sectionTitle}>Ketentuan Pengguna</h2>
                        </div>
                        <p className={sectionText + ' font-semibold text-white'}>
                            2.1 Syarat Penggunaan
                        </p>
                        <ul>
                            <li className={listItem}>
                                Pengguna wajib berusia minimal{' '}
                                <strong className="text-white">13 tahun</strong>, atau mendapat
                                persetujuan orang tua/wali
                            </li>
                            <li className={listItem}>
                                Pengguna bertanggung jawab atas keakuratan data yang dimasukkan
                                (ID game, nomor server, dll)
                            </li>
                            <li className={listItem}>
                                Pengguna dilarang menggunakan layanan Nuvelo untuk tujuan yang
                                melanggar hukum
                            </li>
                            <li className={listItem}>
                                Pengguna dilarang melakukan chargebacks atau pembatalan pembayaran
                                secara sepihak setelah produk terkirim
                            </li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            2.2 Akun Pengguna
                        </p>
                        <ul>
                            <li className={listItem}>
                                Pengguna bertanggung jawab penuh atas keamanan akun dan kata sandi
                                mereka
                            </li>
                            <li className={listItem}>
                                Nuvelo tidak bertanggung jawab atas kerugian akibat akun yang
                                diakses pihak lain
                            </li>
                            <li className={listItem}>
                                Nuvelo berhak menonaktifkan akun yang terindikasi melakukan
                                penyalahgunaan layanan
                            </li>
                        </ul>
                    </section>

                    <section id="s3" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>03</div>
                            <h2 className={sectionTitle}>Layanan & Produk</h2>
                        </div>
                        <p className={sectionText + ' font-semibold text-white'}>
                            3.1 Cakupan Layanan
                        </p>
                        <ul>
                            <li className={listItem}>
                                Topup game mobile: Mobile Legends, Free Fire, PUBG Mobile, dan game
                                lainnya
                            </li>
                            <li className={listItem}>
                                Voucher game PC & console: Steam Wallet, Google Play, PlayStation
                                Network, dan lainnya
                            </li>
                            <li className={listItem}>
                                Produk digital lain yang tersedia di katalog Nuvelo
                            </li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            3.2 Ketersediaan Layanan
                        </p>
                        <ul>
                            <li className={listItem}>
                                Nuvelo beroperasi 24 jam sehari, 7 hari seminggu untuk pemrosesan
                                otomatis
                            </li>
                            <li className={listItem}>
                                Nuvelo berhak menghentikan sementara atau menghapus produk tertentu
                                dari katalog tanpa pemberitahuan sebelumnya
                            </li>
                            <li className={listItem}>
                                Harga produk dapat berubah sewaktu-waktu mengikuti kebijakan
                                penyedia
                            </li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            3.3 Proses Topup
                        </p>
                        <ul>
                            <li className={listItem}>
                                Pengguna memilih produk dan memasukkan data akun game yang dituju
                            </li>
                            <li className={listItem}>
                                Pengguna memilih metode pembayaran dan menyelesaikan pembayaran
                            </li>
                            <li className={listItem}>
                                Sistem memproses topup secara otomatis setelah pembayaran
                                terverifikasi
                            </li>
                            <li className={listItem}>
                                Konfirmasi transaksi dikirim melalui halaman sukses order
                            </li>
                        </ul>
                        <div className={box}>
                            Pengguna wajib memastikan keakuratan{' '}
                            <strong className="text-white">ID game dan server</strong> sebelum
                            melakukan pembayaran. Kesalahan data yang dimasukkan pengguna tidak
                            dapat diklaim sebagai dasar refund.
                        </div>
                    </section>

                    <section id="s4" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>04</div>
                            <h2 className={sectionTitle}>Pembayaran</h2>
                        </div>
                        <p className={sectionText + ' font-semibold text-white'}>
                            4.1 Metode Pembayaran yang Tersedia
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">QRIS</strong> — semua aplikasi yang
                                mendukung QRIS
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">E-wallet</strong> — GoPay, OVO,
                                Dana, dan dompet digital lainnya
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">Virtual Account</strong> — transfer
                                bank via nomor VA
                            </li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            4.2 Ketentuan Pembayaran
                        </p>
                        <ul>
                            <li className={listItem}>
                                Pembayaran wajib diselesaikan sebelum batas waktu yang tertera pada
                                halaman checkout
                            </li>
                            <li className={listItem}>
                                Nuvelo tidak menyimpan data kartu kredit atau informasi rekening
                                bank pengguna
                            </li>
                            <li className={listItem}>
                                Seluruh transaksi pembayaran diproses oleh penyedia payment gateway
                                terpercaya
                            </li>
                            <li className={listItem}>
                                Bukti pembayaran tersimpan di halaman riwayat transaksi akun
                                pengguna
                            </li>
                        </ul>
                        <p className={'mt-5 ' + sectionText + ' font-semibold text-white'}>
                            4.3 Harga & Pajak
                        </p>
                        <ul>
                            <li className={listItem}>
                                Harga yang tertera di website adalah harga final sudah termasuk
                                seluruh biaya
                            </li>
                            <li className={listItem}>
                                Nuvelo tidak memungut biaya tambahan di luar harga yang tertera
                            </li>
                            <li className={listItem}>
                                Pengguna bertanggung jawab atas kewajiban pajak pribadi sesuai
                                ketentuan yang berlaku
                            </li>
                        </ul>
                    </section>

                    <section id="s5" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>05</div>
                            <h2 className={sectionTitle}>Kebijakan Refund & Pengembalian Dana</h2>
                        </div>
                        <p className={sectionText}>
                            Kebijakan ini dirancang untuk melindungi hak pengguna sekaligus menjaga
                            keberlangsungan layanan Nuvelo.
                        </p>
                        <p className={sectionText + ' font-semibold text-white'}>
                            5.1 Kondisi yang Berhak Mendapat Refund
                        </p>
                        <ul>
                            <li className={listItem}>
                                Topup gagal diproses oleh sistem dan produk tidak terkirim ke akun
                                tujuan
                            </li>
                            <li className={listItem}>
                                Pembayaran berhasil namun pesanan tidak diproses lebih dari{' '}
                                <strong className="text-white">30 menit</strong> tanpa keterangan
                            </li>
                            <li className={listItem}>
                                Terjadi duplikasi transaksi (dibayar dua kali untuk pesanan yang
                                sama)
                            </li>
                        </ul>
                        <div className={warning}>
                            <strong className="text-red-100">
                                5.2 Kondisi yang TIDAK Berhak Mendapat Refund:
                            </strong>
                            <ul className="mt-2 space-y-1">
                                <li>
                                    ▸ Pengguna salah memasukkan ID game, nomor server, atau data
                                    akun tujuan
                                </li>
                                <li>▸ Produk telah berhasil terkirim ke akun yang diinput pengguna</li>
                                <li>▸ Pengguna berubah pikiran setelah transaksi selesai</li>
                                <li>
                                    ▸ Akun game pengguna di-ban oleh publisher setelah topup
                                    berhasil
                                </li>
                                <li>
                                    ▸ Permintaan refund diajukan lebih dari{' '}
                                    <strong className="text-red-100">7 × 24 jam</strong> setelah
                                    transaksi
                                </li>
                            </ul>
                        </div>
                        <p className={sectionText + ' font-semibold text-white'}>
                            5.3 Proses Pengajuan Refund
                        </p>
                        <ul>
                            <li className={listItem}>
                                Hubungi Nuvelo melalui WhatsApp{' '}
                                <strong className="text-white">085158330663</strong> dalam 7 × 24
                                jam
                            </li>
                            <li className={listItem}>
                                Sertakan: nomor order, bukti pembayaran, dan penjelasan singkat
                                masalah
                            </li>
                            <li className={listItem}>
                                Nuvelo akan memverifikasi laporan dalam 1 × 24 jam kerja
                            </li>
                            <li className={listItem}>
                                Jika refund disetujui, dana dikembalikan ke metode pembayaran asal
                                dalam 1–3 hari kerja
                            </li>
                        </ul>
                        <div className={box}>
                            Nuvelo berhak meminta bukti tambahan untuk memverifikasi klaim refund.
                            Keputusan Nuvelo atas klaim refund bersifat final.
                        </div>
                    </section>

                    <section id="s6" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>06</div>
                            <h2 className={sectionTitle}>
                                Program Loyalitas (Krysta Coin)
                            </h2>
                        </div>
                        <ul>
                            <li className={listItem}>
                                Krysta Coin adalah program reward internal Nuvelo yang diberikan
                                kepada pengguna aktif
                            </li>
                            <li className={listItem}>
                                Pengguna mendapatkan Krysta Coin sebesar{' '}
                                <strong className="text-white">1%</strong> dari nilai transaksi yang
                                berhasil via QRIS, E-wallet, atau Virtual Account dengan minimum
                                transaksi <strong className="text-white">Rp 10.000</strong>
                            </li>
                            <li className={listItem}>
                                Krysta Coin hanya dapat digunakan untuk mendapatkan diskon pada
                                transaksi berikutnya di Nuvelo
                            </li>
                            <li className={listItem}>
                                Krysta Coin{' '}
                                <strong className="text-white">
                                    tidak dapat diuangkan, dipindahtangankan
                                </strong>
                                , atau ditukar dengan produk fisik
                            </li>
                            <li className={listItem}>
                                Krysta Coin yang tidak digunakan selama{' '}
                                <strong className="text-white">12 bulan</strong> akan hangus secara
                                otomatis
                            </li>
                            <li className={listItem}>
                                Nuvelo berhak mengubah, menangguhkan, atau menghentikan program
                                Krysta Coin dengan pemberitahuan kepada pengguna
                            </li>
                        </ul>
                    </section>

                    <section id="s7" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>07</div>
                            <h2 className={sectionTitle}>Kode Promo & Diskon</h2>
                        </div>
                        <ul>
                            <li className={listItem}>
                                Kode promo hanya berlaku untuk periode waktu yang ditentukan dan
                                tidak dapat diperpanjang
                            </li>
                            <li className={listItem}>
                                Setiap kode promo hanya dapat digunakan{' '}
                                <strong className="text-white">1 (satu) kali</strong> per akun
                                pengguna
                            </li>
                            <li className={listItem}>
                                Kode promo tidak dapat digabungkan dengan promo atau diskon lain
                                kecuali dinyatakan sebaliknya
                            </li>
                            <li className={listItem}>
                                Nuvelo berhak menonaktifkan kode promo yang terdeteksi disalahgunakan
                            </li>
                            <li className={listItem}>
                                Penyalahgunaan kode promo dapat mengakibatkan penangguhan akun
                            </li>
                        </ul>
                    </section>

                    <section id="s8" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>08</div>
                            <h2 className={sectionTitle}>Batasan Tanggung Jawab</h2>
                        </div>
                        <ul>
                            <li className={listItem}>
                                Nuvelo tidak bertanggung jawab atas gangguan layanan yang disebabkan
                                oleh pihak ketiga (publisher game, penyedia payment, atau gangguan
                                internet)
                            </li>
                            <li className={listItem}>
                                Nuvelo tidak bertanggung jawab atas kerugian tidak langsung yang
                                timbul dari penggunaan layanan
                            </li>
                            <li className={listItem}>
                                Nuvelo tidak berafiliasi dengan publisher game manapun kecuali
                                dinyatakan secara resmi
                            </li>
                        </ul>
                        <div className={box}>
                            Tanggung jawab maksimal Nuvelo terbatas pada{' '}
                            <strong className="text-white">nilai transaksi</strong> yang bermasalah.
                        </div>
                    </section>

                    <section id="s9" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>09</div>
                            <h2 className={sectionTitle}>Perubahan Ketentuan</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo berhak mengubah syarat dan ketentuan ini sewaktu-waktu. Perubahan
                            akan diumumkan melalui website{' '}
                            <strong className="text-white">nuvelo.id</strong> dan/atau media sosial
                            resmi Nuvelo. Penggunaan layanan setelah perubahan dianggap sebagai
                            persetujuan terhadap syarat yang baru.
                        </p>
                    </section>

                    <section id="s10" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>10</div>
                            <h2 className={sectionTitle}>Hukum yang Berlaku</h2>
                        </div>
                        <p className={sectionText}>
                            Syarat dan ketentuan ini diatur oleh{' '}
                            <strong className="text-white">hukum Republik Indonesia</strong>. Setiap
                            sengketa yang timbul akan diselesaikan secara musyawarah. Apabila tidak
                            tercapai kesepakatan, sengketa diselesaikan melalui jalur hukum yang
                            berlaku di Indonesia.
                        </p>
                    </section>

                    <section id="s11" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>11</div>
                            <h2 className={sectionTitle}>Hubungi Kami</h2>
                        </div>
                        <p className={sectionText}>
                            Apabila Anda memiliki pertanyaan atau memerlukan klarifikasi mengenai
                            Syarat dan Ketentuan ini, silakan menghubungi tim kami:
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
                        <div className="mt-6 flex items-center gap-3">
                            <Link
                                href="/kebijakan-privasi"
                                className="rounded-md border border-primary/60 bg-primary/10 px-4 py-2 text-xs font-semibold text-primary transition hover:bg-primary/20"
                            >
                                Lihat Kebijakan Privasi
                            </Link>
                        </div>
                    </section>
                </div>
            </section>
        </GuestLayout>
    );
}



