import { useEffect, useState } from 'react';

const COUNTRY_CODE = '+62';

interface Props {
    onChange: (phone: string) => void;
}

export default function StepAccountDetail({ onChange }: Props) {
    // simpan DIGIT SAJA (tanpa +62)
    const [digits, setDigits] = useState('');

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let value = e.target.value.replace(/\D/g, '');

        // buang 62 kalau user ngetik ulang
        if (value.startsWith('62')) {
            value = value.slice(2);
        }

        // limit 11 digit
        setDigits(value.slice(0, 11));
    };

    // 🔥 KUNCI: kirim ke parent
    useEffect(() => {
        if (digits.length >= 9) {
            onChange(COUNTRY_CODE + digits);
        } else {
            onChange('');
        }
    }, [digits]);

    const formatted = formatIndoPhone(digits);

    return (
        <div className="mt-12 flex w-full flex-col gap-6">
            <div className="overflow-hidden rounded-xl bg-client-card">
                {/* Header */}
                <div className="flex h-12 overflow-hidden rounded-t-xl">
                    <div className="flex w-12 items-center justify-center bg-client-primary text-lg font-bold text-white">2</div>
                    <div className="flex flex-1 items-center bg-[#5f5968] px-4">
                        <h4 className="text-sm font-semibold text-white">Detail Kontak</h4>
                    </div>
                </div>

                {/* Body */}
                <div className="p-5">
                    <label className="mb-1 block text-xs text-white/70">No. WhatsApp</label>

                    <input
                        type="tel"
                        value={formatted}
                        onChange={handleChange}
                        placeholder="+62 8xxx xxxx xxxx"
                        className="w-full rounded-md border border-white/10 bg-[#2b2834] px-3 py-2 text-sm text-white focus:border-client-primary focus:outline-none"
                    />

                    <p className="mt-3 text-xs text-white/50">Contoh: +62 867 0123 8123</p>
                </div>
            </div>
        </div>
    );
}

function formatIndoPhone(digits: string) {
    const p1 = digits.slice(0, 3);
    const p2 = digits.slice(3, 7);
    const p3 = digits.slice(7, 11);

    return [COUNTRY_CODE, p1, p2, p3].filter(Boolean).join(' ');
}
