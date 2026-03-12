import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

const tocItems = [
    { id: 's1', title: 'Pendahuluan & Definisi' },
    { id: 's2', title: 'Pendaftaran Akun' },
    { id: 's3', title: 'Layanan Topup Digital' },
    { id: 's4', title: 'Harga & Pembayaran' },
    { id: 's5', title: 'Kebijakan Refund' },
    { id: 's6', title: 'Larangan & Penyalahgunaan' },
    { id: 's7', title: 'Tanggung Jawab Pengguna' },
    { id: 's8', title: 'Batasan Tanggung Jawab' },
    { id: 's9', title: 'Penghentian Layanan' },
    { id: 's10', title: 'Hukum yang Berlaku' },
    { id: 's11', title: 'Perubahan Ketentuan' },
    { id: 's12', title: 'Hubungi Kami' },
];

const sectionBase =
    'rounded-2xl border border-white/10 bg-[#201f2c] p-5 sm:p-7 md:p-8';
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

            <section className="relative overflow-hidden border-b border-white/10 bg-[#241f38]">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(131,39,216,0.25),transparent_45%),radial-gradient(circle_at_80%_0%,rgba(76,201,240,0.15),transparent_35%)]" />
                <div className="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
                    <span className="inline-flex rounded-full border border-primary/50 bg-primary/15 px-4 py-1 text-[11px] font-semibold tracking-[0.2em] text-primary uppercase">
                        Dokumen Resmi
                    </span>
                    <h1 className="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl">
                        Syarat & Ketentuan
                    </h1>
                    <p className="mt-3 text-sm text-gray-400">
                        Berlaku sejak: 11 Maret 2026 · Versi 1.0
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div className="mb-8 rounded-2xl border border-primary/30 bg-[#211c34] p-5 sm:p-7">
                    <h2 className="mb-4 text-base font-bold text-white">
                        Daftar Isi
                    </h2>
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
                            <h2 className={sectionTitle}>
                                Pendahuluan & Definisi
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Selamat datang di{' '}
                            <strong className="text-white">TopupStore</strong>.
                            Syarat dan Ketentuan ini merupakan perjanjian yang
                            mengikat secara hukum antara Anda (
                            <strong className="text-white">Pengguna</strong>)
                            dan TopupStore (
                            <strong className="text-white">Perusahaan</strong>,{' '}
                            <strong className="text-white">Kami</strong>) selaku
                            penyedia layanan topup digital.
                        </p>
                        <p className={sectionText}>
                            Dengan mendaftar, mengakses, atau menggunakan
                            layanan kami, Anda menyatakan telah berusia minimal{' '}
                            <strong className="text-white">
                                17 (tujuh belas) tahun
                            </strong>
                            atau memiliki izin dari orang tua atau wali yang
                            sah, serta menyetujui seluruh ketentuan dalam
                            dokumen ini.
                        </p>
                        <div className={box}>
                            <p>
                                <strong className="text-white">
                                    Definisi Utama:
                                </strong>
                            </p>
                            <p>
                                <em>Platform</em> merujuk pada website,
                                aplikasi, dan seluruh saluran digital
                                TopupStore.
                            </p>
                            <p>
                                <em>Layanan</em> merujuk pada jasa topup item
                                dan mata uang dalam game untuk berbagai judul
                                game.
                            </p>
                            <p>
                                <em>Transaksi</em> merujuk pada setiap pembelian
                                atau pembayaran yang dilakukan melalui Platform.
                            </p>
                        </div>
                    </section>

                    <section id="s2" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>02</div>
                            <h2 className={sectionTitle}>Pendaftaran Akun</h2>
                        </div>
                        <p className={sectionText}>
                            Untuk mengakses fitur tertentu pada Platform, Anda
                            diwajibkan membuat akun pengguna. Dalam proses
                            pendaftaran, Anda wajib:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Memberikan informasi yang akurat, lengkap, dan
                                terkini
                            </li>
                            <li className={listItem}>
                                Menjaga kerahasiaan kata sandi dan informasi
                                akun Anda
                            </li>
                            <li className={listItem}>
                                Segera memberitahukan kami apabila terjadi akses
                                tidak sah ke akun Anda
                            </li>
                            <li className={listItem}>
                                Tidak membuat lebih dari satu akun per individu
                            </li>
                            <li className={listItem}>
                                Tidak menggunakan identitas atau data milik
                                orang lain
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Anda bertanggung jawab penuh atas seluruh aktivitas
                            yang terjadi di bawah akun Anda. TopupStore tidak
                            bertanggung jawab atas kerugian yang timbul akibat
                            kelalaian Anda dalam menjaga keamanan akun.
                        </p>
                    </section>

                    <section id="s3" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>03</div>
                            <h2 className={sectionTitle}>
                                Layanan Topup Digital
                            </h2>
                        </div>
                        <p className={sectionText}>
                            TopupStore menyediakan layanan pengisian saldo dan
                            item digital untuk berbagai platform game, pulsa,
                            data, dan e-wallet. Proses topup dilakukan secara
                            otomatis setelah pembayaran berhasil diverifikasi.
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Mobile Legends Bang Bang:
                                </strong>{' '}
                                Diamond, Weekly Diamond Pass, Starlight Member
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Free Fire:
                                </strong>{' '}
                                Diamond, Diamond Royale, Membership
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    PUBG Mobile:
                                </strong>{' '}
                                UC, Royal Pass
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Genshin Impact:
                                </strong>{' '}
                                Genesis Crystals, Welkin Moon
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Honkai: Star Rail:
                                </strong>{' '}
                                Oneiric Shard, Express Supply Pass
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Dan berbagai game mobile & PC lainnya
                                </strong>
                            </li>
                        </ul>
                        <div className={box}>
                            Pengguna wajib memastikan kebenaran{' '}
                            <strong className="text-white">
                                User ID dan Server atau Zone game
                            </strong>{' '}
                            yang diinput sebelum menyelesaikan pembayaran. Item
                            game yang telah berhasil masuk ke akun tujuan{' '}
                            <strong className="text-white">
                                tidak dapat ditarik kembali
                            </strong>
                            , dan kesalahan input data oleh Pengguna bukan
                            merupakan tanggung jawab TopupStore.
                        </div>
                    </section>

                    <section id="s4" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>04</div>
                            <h2 className={sectionTitle}>Harga & Pembayaran</h2>
                        </div>
                        <p className={sectionText}>
                            Seluruh harga yang tercantum di platform dinyatakan
                            dalam
                            <strong className="text-white">
                                {' '}
                                Rupiah Indonesia (IDR)
                            </strong>{' '}
                            dan sudah termasuk biaya layanan. Harga dapat
                            berubah sewaktu-waktu tanpa pemberitahuan
                            sebelumnya.
                        </p>
                        <p className={sectionText}>
                            Kami menerima berbagai metode pembayaran yang
                            diproses melalui
                            <strong className="text-white"> Tripay</strong>{' '}
                            selaku penyedia layanan pembayaran berlisensi,
                            meliputi:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Transfer bank (BCA, BNI, BRI, Mandiri, BSI)
                            </li>
                            <li className={listItem}>
                                Virtual Account semua bank
                            </li>
                            <li className={listItem}>
                                QRIS (Quick Response Code Indonesian Standard)
                            </li>
                            <li className={listItem}>
                                Dompet digital (GoPay, OVO, DANA, ShopeePay)
                            </li>
                            <li className={listItem}>
                                Gerai retail (Alfamart, Indomaret)
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Transaksi yang telah dibayarkan akan diproses dalam
                            kurun waktu
                            <strong className="text-white">
                                {' '}
                                1-5 menit
                            </strong>{' '}
                            setelah konfirmasi pembayaran. Pada kondisi
                            tertentu, proses dapat memakan waktu hingga
                            <strong className="text-white"> 1x24 jam</strong>.
                        </p>
                    </section>

                    <section id="s5" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>05</div>
                            <h2 className={sectionTitle}>
                                Kebijakan Refund & Pembatalan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Mengingat sifat layanan topup yang bersifat
                            <strong className="text-white">
                                {' '}
                                digital dan instan
                            </strong>
                            , pada umumnya transaksi yang telah berhasil
                            diproses{' '}
                            <strong className="text-white">
                                tidak dapat dibatalkan atau dikembalikan
                            </strong>
                            .
                        </p>
                        <p className={sectionText}>
                            Refund atau kompensasi hanya dapat diproses dalam
                            kondisi berikut:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Kegagalan sistem di pihak TopupStore yang
                                mengakibatkan item atau saldo tidak terkirim
                            </li>
                            <li className={listItem}>
                                Transaksi ganda (double charge) akibat kesalahan
                                sistem
                            </li>
                            <li className={listItem}>
                                Pembayaran berhasil namun pesanan tidak diproses
                                dalam 1x24 jam
                            </li>
                        </ul>
                        <div className={warning}>
                            <strong className="text-red-100">Perhatian:</strong>{' '}
                            Refund tidak dapat diproses apabila kesalahan data
                            target topup dilakukan oleh Pengguna, akun game atau
                            platform target diblokir oleh pihak pengembang, atau
                            klaim diajukan lebih dari 24 jam sejak transaksi.
                        </div>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Pengajuan refund dapat dilakukan melalui email atau
                            WhatsApp customer support kami dengan menyertakan
                            bukti pembayaran dan detail transaksi.
                        </p>
                    </section>

                    <section id="s6" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>06</div>
                            <h2 className={sectionTitle}>
                                Larangan & Penyalahgunaan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Pengguna dilarang keras menggunakan Platform untuk
                            aktivitas yang melanggar hukum atau merugikan pihak
                            lain, termasuk namun tidak terbatas pada:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Melakukan penipuan, pemalsuan identitas, atau
                                penggunaan metode pembayaran tidak sah
                            </li>
                            <li className={listItem}>
                                Melakukan chargebacks yang tidak berdasar
                                (friendly fraud)
                            </li>
                            <li className={listItem}>
                                Mengeksploitasi bug, celah sistem, atau promosi
                                secara tidak wajar
                            </li>
                            <li className={listItem}>
                                Menggunakan layanan untuk tujuan pencucian uang
                            </li>
                            <li className={listItem}>
                                Menjual kembali produk digital yang diperoleh
                                dari TopupStore secara tidak sah
                            </li>
                            <li className={listItem}>
                                Melakukan serangan siber, DDoS, atau upaya
                                pembobolan sistem
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Pelanggaran terhadap ketentuan ini dapat
                            mengakibatkan pembekuan atau penghapusan akun,
                            pembatalan transaksi, dan atau pelaporan kepada
                            pihak berwajib.
                        </p>
                    </section>

                    <section id="s7" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>07</div>
                            <h2 className={sectionTitle}>
                                Tanggung Jawab Pengguna
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Pengguna bertanggung jawab penuh untuk:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Memastikan akurasi data yang diinput sebelum
                                menyelesaikan transaksi
                            </li>
                            <li className={listItem}>
                                Memastikan akun tujuan topup aktif dan tidak
                                dalam status banned atau suspended
                            </li>
                            <li className={listItem}>
                                Memiliki hak penggunaan yang sah atas metode
                                pembayaran yang digunakan
                            </li>
                            <li className={listItem}>
                                Mematuhi syarat dan ketentuan platform atau game
                                tujuan topup
                            </li>
                            <li className={listItem}>
                                Melaporkan kendala transaksi dalam 24 jam sejak
                                pembayaran dilakukan
                            </li>
                        </ul>
                    </section>

                    <section id="s8" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>08</div>
                            <h2 className={sectionTitle}>
                                Batasan Tanggung Jawab
                            </h2>
                        </div>
                        <p className={sectionText}>
                            TopupStore tidak bertanggung jawab atas kerugian
                            yang timbul akibat:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Gangguan server pihak ketiga yang berada di luar
                                kendali kami
                            </li>
                            <li className={listItem}>
                                Kesalahan data yang dimasukkan oleh Pengguna
                            </li>
                            <li className={listItem}>
                                Pemblokiran atau pembatasan akun oleh pihak
                                pengembang game atau platform
                            </li>
                            <li className={listItem}>
                                Gangguan koneksi internet atau perangkat milik
                                Pengguna
                            </li>
                            <li className={listItem}>
                                Kejadian force majeure seperti bencana alam atau
                                gangguan infrastruktur nasional
                            </li>
                        </ul>
                        <div className={box}>
                            Tanggung jawab maksimal TopupStore dalam setiap
                            klaim tidak akan melebihi nilai transaksi yang
                            dipersengketakan.
                        </div>
                    </section>

                    <section id="s9" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>09</div>
                            <h2 className={sectionTitle}>
                                Penghentian Layanan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            TopupStore berhak untuk membekukan atau menghentikan
                            akun Pengguna secara sepihak apabila ditemukan
                            pelanggaran terhadap Syarat dan Ketentuan ini.
                        </p>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Pengguna juga dapat menghapus akunnya kapan saja
                            dengan menghubungi tim customer support kami.
                            Penghapusan akun tidak akan menghapus kewajiban yang
                            telah timbul sebelum tanggal penghapusan.
                        </p>
                    </section>

                    <section id="s10" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>10</div>
                            <h2 className={sectionTitle}>
                                Hukum yang Berlaku & Penyelesaian Sengketa
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Syarat dan Ketentuan ini diatur oleh dan ditafsirkan
                            sesuai dengan
                            <strong className="text-white">
                                {' '}
                                hukum Republik Indonesia
                            </strong>
                            . Setiap sengketa yang timbul akan diselesaikan
                            secara musyawarah dalam jangka waktu 30 hari.
                        </p>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Apabila musyawarah tidak mencapai kesepakatan, para
                            pihak setuju untuk menyelesaikan sengketa melalui{' '}
                            <strong className="text-white">BANI</strong> atau
                            Pengadilan Negeri yang berwenang di Jakarta,
                            Indonesia.
                        </p>
                    </section>

                    <section id="s11" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>11</div>
                            <h2 className={sectionTitle}>
                                Perubahan Ketentuan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            TopupStore berhak mengubah, memperbarui, atau
                            merevisi Syarat dan Ketentuan ini kapan saja.
                            Perubahan material akan diinformasikan melalui email
                            terdaftar atau pemberitahuan di Platform minimal
                            <strong className="text-white">
                                {' '}
                                7 (tujuh) hari
                            </strong>{' '}
                            sebelum berlaku.
                        </p>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Kelanjutan penggunaan layanan TopupStore setelah
                            tanggal efektif perubahan merupakan penerimaan Anda
                            terhadap ketentuan yang telah diperbarui.
                        </p>
                    </section>

                    <section id="s12" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>12</div>
                            <h2 className={sectionTitle}>Hubungi Kami</h2>
                        </div>
                        <p className={sectionText}>
                            Apabila Anda memiliki pertanyaan atau memerlukan
                            klarifikasi mengenai Syarat dan Ketentuan ini,
                            silakan menghubungi tim kami:
                        </p>
                        <div className={box}>
                            <p>
                                <strong className="text-white">
                                    TopupStore
                                </strong>
                            </p>
                            <p>Email: support@topupstore.id</p>
                            <p>WhatsApp: +62 812-XXXX-XXXX</p>
                            <p>Jam Operasional: Senin-Jumat, 09.00-17.00 WIB</p>
                            <p>Respon dalam: 1x24 jam kerja</p>
                        </div>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Dengan menggunakan layanan TopupStore, Anda
                            menyatakan telah membaca, memahami, dan menyetujui
                            seluruh Syarat dan Ketentuan yang berlaku.
                        </p>
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
