import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';

const tocItems = [
    { id: 's1', title: 'Pendahuluan' },
    { id: 's2', title: 'Data yang Kami Kumpulkan' },
    { id: 's3', title: 'Tujuan Penggunaan Data' },
    { id: 's4', title: 'Keamanan Data' },
    { id: 's5', title: 'Penyimpanan & Retensi' },
    { id: 's6', title: 'Hak Pengguna' },
    { id: 's7', title: 'Cookie & Teknologi Serupa' },
    { id: 's8', title: 'Pihak Ketiga' },
    { id: 's9', title: 'Perubahan Kebijakan' },
    { id: 's10', title: 'Hubungi Kami' },
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

export default function KebijakanPrivasiPage() {
    return (
        <GuestLayout>
            <Head title="Kebijakan Privasi" />

            <section className="relative overflow-hidden border-b border-white/10 bg-[#241f38]">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(131,39,216,0.25),transparent_45%),radial-gradient(circle_at_80%_0%,rgba(76,201,240,0.15),transparent_35%)]" />
                <div className="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
                    <span className="inline-flex rounded-full border border-primary/50 bg-primary/15 px-4 py-1 text-[11px] font-semibold tracking-[0.2em] text-primary uppercase">
                        Dokumen Resmi
                    </span>
                    <h1 className="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl">
                        Kebijakan Privasi
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
                            <h2 className={sectionTitle}>Pendahuluan</h2>
                        </div>
                        <p className={sectionText}>
                            Nuvelo (
                            <strong className="text-white">Perusahaan</strong>,
                            <strong className="text-white"> Kami</strong>)
                            berkomitmen untuk melindungi privasi dan keamanan
                            data pribadi setiap pengguna yang mengakses dan
                            menggunakan layanan kami. Kebijakan Privasi ini
                            menjelaskan bagaimana kami mengumpulkan,
                            menggunakan, menyimpan, dan melindungi informasi
                            Anda.
                        </p>
                        <p className={sectionText}>
                            Dengan mengakses atau menggunakan platform Nuvelo
                            termasuk website, aplikasi, dan layanan terkait,
                            Anda menyatakan telah membaca, memahami, dan
                            menyetujui Kebijakan Privasi ini.
                        </p>
                        <div className={box}>
                            <strong className="text-white">Yurisdiksi:</strong>{' '}
                            Kebijakan ini disusun sesuai ketentuan Undang-Undang
                            No. 27 Tahun 2022 tentang Pelindungan Data Pribadi
                            Republik Indonesia dan regulasi yang berlaku.
                        </div>
                    </section>

                    <section id="s2" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>02</div>
                            <h2 className={sectionTitle}>
                                Data yang Kami Kumpulkan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Dalam rangka menjalankan layanan topup digital, kami
                            dapat mengumpulkan data sebagai berikut:
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Data Identitas:
                                </strong>{' '}
                                Nama lengkap, alamat email, nomor telepon
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Data Akun Game:
                                </strong>{' '}
                                User ID, Nickname, Server atau Zone, dan
                                informasi lain yang diperlukan untuk proses
                                topup
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Data Transaksi:
                                </strong>{' '}
                                Riwayat pembelian, nominal transaksi, metode
                                pembayaran
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Data Teknis:
                                </strong>{' '}
                                Alamat IP, jenis browser, sistem operasi, dan
                                data log akses
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Data Komunikasi:
                                </strong>{' '}
                                Pesan yang Anda kirimkan kepada tim customer
                                support
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Kami <strong className="text-white">tidak</strong>{' '}
                            mengumpulkan data sensitif seperti nomor KTP, data
                            biometrik, atau informasi medis.
                        </p>
                    </section>

                    <section id="s3" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>03</div>
                            <h2 className={sectionTitle}>
                                Tujuan Penggunaan Data
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Data pribadi Anda digunakan semata-mata untuk
                            keperluan operasional layanan, antara lain:
                        </p>
                        <ul>
                            <li className={listItem}>
                                Memproses dan memverifikasi transaksi topup
                                digital
                            </li>
                            <li className={listItem}>
                                Mengirimkan konfirmasi pesanan dan notifikasi
                                transaksi
                            </li>
                            <li className={listItem}>
                                Memberikan dukungan pelanggan
                            </li>
                            <li className={listItem}>
                                Mendeteksi dan mencegah aktivitas penipuan
                            </li>
                            <li className={listItem}>
                                Mematuhi kewajiban hukum dan regulasi yang
                                berlaku
                            </li>
                            <li className={listItem}>
                                Meningkatkan kualitas layanan berdasarkan
                                analisis penggunaan
                            </li>
                        </ul>
                        <div className={box}>
                            Kami{' '}
                            <strong className="text-white">
                                tidak menjual, menyewakan, atau memperdagangkan
                            </strong>{' '}
                            data pribadi Anda kepada pihak ketiga untuk tujuan
                            komersial tanpa persetujuan eksplisit Anda.
                        </div>
                    </section>

                    <section id="s4" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>04</div>
                            <h2 className={sectionTitle}>Keamanan Data</h2>
                        </div>
                        <p className={sectionText}>
                            Kami menerapkan langkah-langkah keamanan teknis dan
                            organisasional yang memadai untuk melindungi data
                            pribadi Anda dari akses tidak sah, pengungkapan,
                            perubahan, atau penghancuran.
                        </p>
                        <ul>
                            <li className={listItem}>
                                Enkripsi data menggunakan protokol SSL atau TLS
                                pada semua transmisi data
                            </li>
                            <li className={listItem}>
                                Akses data dibatasi hanya kepada karyawan dengan
                                kebutuhan operasional yang sah
                            </li>
                            <li className={listItem}>
                                Sistem pemantauan keamanan yang aktif dan
                                berkala
                            </li>
                            <li className={listItem}>
                                Prosedur penanggulangan insiden keamanan data
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Meskipun demikian, tidak ada sistem keamanan yang
                            sempurna. Kami mendorong Anda untuk menjaga
                            kerahasiaan kredensial akun Anda.
                        </p>
                    </section>

                    <section id="s5" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>05</div>
                            <h2 className={sectionTitle}>
                                Penyimpanan & Retensi Data
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Data pribadi Anda akan disimpan selama Anda memiliki
                            akun aktif di platform kami, atau selama diperlukan
                            untuk tujuan yang disebutkan dalam kebijakan ini.
                        </p>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Data transaksi akan disimpan minimal selama
                            <strong className="text-white">
                                {' '}
                                5 (lima) tahun
                            </strong>{' '}
                            sesuai kewajiban hukum perpajakan dan pencatatan
                            keuangan yang berlaku di Indonesia. Setelah periode
                            retensi berakhir, data akan dihapus atau dianonimkan
                            secara aman.
                        </p>
                    </section>

                    <section id="s6" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>06</div>
                            <h2 className={sectionTitle}>Hak Pengguna</h2>
                        </div>
                        <p className={sectionText}>
                            Sesuai dengan UU PDP, Anda memiliki hak-hak berikut
                            terkait data pribadi Anda:
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Hak Akses:
                                </strong>{' '}
                                Meminta salinan data pribadi yang kami simpan
                                tentang Anda
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Hak Koreksi:
                                </strong>{' '}
                                Meminta perbaikan data yang tidak akurat atau
                                tidak lengkap
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Hak Penghapusan:
                                </strong>{' '}
                                Meminta penghapusan data dalam kondisi tertentu
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Hak Penarikan Persetujuan:
                                </strong>{' '}
                                Menarik kembali persetujuan pemrosesan data
                                sewaktu-waktu
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Hak Pengaduan:
                                </strong>{' '}
                                Mengajukan pengaduan kepada otoritas pengawas
                                yang berwenang
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Untuk menggunakan hak-hak di atas, silakan hubungi
                            kami melalui informasi kontak yang tercantum di
                            bagian terakhir dokumen ini.
                        </p>
                    </section>

                    <section id="s7" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>07</div>
                            <h2 className={sectionTitle}>
                                Cookie & Teknologi Serupa
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Platform kami menggunakan cookie dan teknologi
                            pelacakan serupa untuk meningkatkan pengalaman
                            pengguna, menganalisis trafik, dan menjaga keamanan
                            sesi.
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Cookie Esensial:
                                </strong>{' '}
                                Diperlukan untuk fungsi dasar platform dan tidak
                                dapat dinonaktifkan
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Cookie Analitik:
                                </strong>{' '}
                                Membantu kami memahami cara pengguna
                                berinteraksi dengan platform
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Cookie Keamanan:
                                </strong>{' '}
                                Membantu mendeteksi dan mencegah aktivitas fraud
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Anda dapat mengatur preferensi cookie melalui
                            pengaturan browser Anda, namun menonaktifkan cookie
                            tertentu dapat mempengaruhi fungsionalitas platform.
                        </p>
                    </section>

                    <section id="s8" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>08</div>
                            <h2 className={sectionTitle}>Pihak Ketiga</h2>
                        </div>
                        <p className={sectionText}>
                            Dalam menjalankan layanan, kami bekerja sama dengan
                            pihak ketiga terpercaya, termasuk:
                        </p>
                        <ul>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Penyedia Pembayaran:
                                </strong>{' '}
                                Tripay dan gateway pembayaran lainnya yang
                                berlisensi dari Bank Indonesia
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Penyedia Infrastruktur:
                                </strong>{' '}
                                Layanan hosting dan komputasi awan yang
                                tersertifikasi
                            </li>
                            <li className={listItem}>
                                <strong className="text-white">
                                    Penyedia Analitik:
                                </strong>{' '}
                                Alat analisis yang membantu kami meningkatkan
                                layanan
                            </li>
                        </ul>
                        <p className="mt-4 text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Setiap mitra pihak ketiga terikat dengan perjanjian
                            kerahasiaan data dan wajib mematuhi standar keamanan
                            yang setara dengan standar kami.
                        </p>
                    </section>

                    <section id="s9" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>09</div>
                            <h2 className={sectionTitle}>
                                Perubahan Kebijakan
                            </h2>
                        </div>
                        <p className={sectionText}>
                            Kami berhak untuk memperbarui Kebijakan Privasi ini
                            dari waktu ke waktu. Setiap perubahan material akan
                            diberitahukan kepada Anda melalui email terdaftar
                            atau notifikasi di platform setidaknya
                            <strong className="text-white">
                                {' '}
                                14 (empat belas) hari
                            </strong>{' '}
                            sebelum perubahan berlaku.
                        </p>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Penggunaan layanan kami setelah tanggal berlakunya
                            perubahan dianggap sebagai penerimaan terhadap
                            kebijakan yang diperbarui.
                        </p>
                    </section>

                    <section id="s10" className={sectionBase}>
                        <div className={sectionHeader}>
                            <div className={sectionNum}>10</div>
                            <h2 className={sectionTitle}>Hubungi Kami</h2>
                        </div>
                        <p className={sectionText}>
                            Apabila Anda memiliki pertanyaan, permintaan, atau
                            pengaduan terkait Kebijakan Privasi ini, silakan
                            menghubungi kami:
                        </p>
                        <div className={box}>
                            <p>
                                <strong className="text-white">Nuvelo</strong>
                            </p>
                            <p>Email: privacy@Nuvelo.id</p>
                            <p>WhatsApp: +62 812-XXXX-XXXX</p>
                            <p>Jam Operasional: Senin-Jumat, 09.00-17.00 WIB</p>
                        </div>
                        <p className="text-sm leading-7 text-gray-300 sm:text-[15px]">
                            Kami berkomitmen untuk merespons setiap pertanyaan
                            atau permintaan dalam waktu{' '}
                            <strong className="text-white">
                                3 (tiga) hari kerja
                            </strong>
                            .
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
