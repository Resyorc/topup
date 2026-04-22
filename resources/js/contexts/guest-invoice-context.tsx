import { createContext, useContext, useState, useEffect, useCallback  } from 'react';
import type {ReactNode} from 'react';

const STORAGE_KEY = 'guest_invoices';
const REVIEWED_PREFIX = 'reviewed_';

interface GuestInvoiceContextValue {
    /** List of invoice IDs owned by this guest */
    invoices: string[];
    /** Register a newly created invoice */
    addInvoice: (invoiceId: string) => void;
    /** Check if this guest owns the given invoice */
    ownsInvoice: (invoiceId: string) => boolean;
    /** Check if this guest has already reviewed the given invoice */
    hasReviewed: (invoiceId: string) => boolean;
    /** Mark an invoice as reviewed */
    markReviewed: (invoiceId: string) => void;
}

const GuestInvoiceContext = createContext<GuestInvoiceContextValue | null>(null);

function readInvoices(): string[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function persistInvoices(invoices: string[]) {
    if (typeof window === 'undefined') return;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(invoices));
}

export function GuestInvoiceProvider({ children }: { children: ReactNode }) {
    const [invoices, setInvoices] = useState<string[]>(() => readInvoices());

    // Sync to localStorage whenever the list changes
    useEffect(() => {
        persistInvoices(invoices);
    }, [invoices]);

    const addInvoice = useCallback((invoiceId: string) => {
        setInvoices((prev) => {
            if (prev.includes(invoiceId)) return prev;
            return [...prev, invoiceId];
        });
    }, []);

    const ownsInvoice = useCallback(
        (invoiceId: string) => invoices.includes(invoiceId),
        [invoices],
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
            value={{ invoices, addInvoice, ownsInvoice, hasReviewed, markReviewed }}
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

