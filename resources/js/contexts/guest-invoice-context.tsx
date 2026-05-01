import { createContext, useContext, useState, useEffect, useCallback  } from 'react';
import type {ReactNode} from 'react';

const STORAGE_KEY = 'guest_invoices';
const REVIEWED_PREFIX = 'reviewed_';

interface GuestInvoiceRecord {
    invoice_id: string;
    guest_token?: string | null;
}

interface GuestInvoiceContextValue {
    /** List of invoice IDs owned by this guest */
    invoices: string[];
    /** Register a newly created invoice */
    addInvoice: (invoiceId: string, guestToken?: string | null) => void;
    /** Check if this guest owns the given invoice */
    ownsInvoice: (invoiceId: string) => boolean;
    /** Resolve guest token for a locally-owned invoice */
    getGuestToken: (invoiceId: string) => string | null;
    /** Check if this guest has already reviewed the given invoice */
    hasReviewed: (invoiceId: string) => boolean;
    /** Mark an invoice as reviewed */
    markReviewed: (invoiceId: string) => void;
}

const GuestInvoiceContext = createContext<GuestInvoiceContextValue | null>(null);

function normalizeRecord(value: unknown): GuestInvoiceRecord | null {
    if (typeof value === 'string' && value.trim() !== '') {
        return { invoice_id: value };
    }

    if (
        value &&
        typeof value === 'object' &&
        'invoice_id' in value &&
        typeof (value as GuestInvoiceRecord).invoice_id === 'string'
    ) {
        return {
            invoice_id: (value as GuestInvoiceRecord).invoice_id,
            guest_token: (value as GuestInvoiceRecord).guest_token ?? null,
        };
    }

    return null;
}

function readInvoiceRecords(): GuestInvoiceRecord[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];

        return parsed
            .map(normalizeRecord)
            .filter((record): record is GuestInvoiceRecord => record !== null);
    } catch {
        return [];
    }
}

function persistInvoices(invoices: GuestInvoiceRecord[]) {
    if (typeof window === 'undefined') return;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(invoices));
}

export function GuestInvoiceProvider({ children }: { children: ReactNode }) {
    const [invoiceRecords, setInvoiceRecords] = useState<GuestInvoiceRecord[]>(() => readInvoiceRecords());
    const invoices = invoiceRecords.map((record) => record.invoice_id);

    // Sync to localStorage whenever the list changes
    useEffect(() => {
        persistInvoices(invoiceRecords);
    }, [invoiceRecords]);

    const addInvoice = useCallback((invoiceId: string, guestToken?: string | null) => {
        setInvoiceRecords((prev) => {
            const existing = prev.find((record) => record.invoice_id === invoiceId);
            if (existing) {
                return prev.map((record) =>
                    record.invoice_id === invoiceId
                        ? { ...record, guest_token: guestToken ?? record.guest_token ?? null }
                        : record,
                );
            }

            return [...prev, { invoice_id: invoiceId, guest_token: guestToken ?? null }];
        });
    }, []);

    const ownsInvoice = useCallback(
        (invoiceId: string) => invoiceRecords.some((record) => record.invoice_id === invoiceId),
        [invoiceRecords],
    );

    const getGuestToken = useCallback(
        (invoiceId: string) =>
            invoiceRecords.find((record) => record.invoice_id === invoiceId)?.guest_token ?? null,
        [invoiceRecords],
    );

    const hasReviewed = useCallback((invoiceId: string) => {
        if (typeof window === 'undefined') return false;
        try {
            return localStorage.getItem(`${REVIEWED_PREFIX}${invoiceId}`) === '1';
        } catch {
            return false;
        }
    }, []);

    const markReviewed = useCallback((invoiceId: string) => {
        if (typeof window === 'undefined') return;
        try {
            localStorage.setItem(`${REVIEWED_PREFIX}${invoiceId}`, '1');
        } catch {
            // localStorage full or unavailable — silently ignore
        }
    }, []);

    return (
        <GuestInvoiceContext.Provider
            value={{ invoices, addInvoice, ownsInvoice, getGuestToken, hasReviewed, markReviewed }}
        >
            {children}
        </GuestInvoiceContext.Provider>
    );
}

export function useGuestInvoice(): GuestInvoiceContextValue {
    const ctx = useContext(GuestInvoiceContext);
    if (!ctx) {
        throw new Error('useGuestInvoice must be used within <GuestInvoiceProvider>');
    }
    return ctx;
}

