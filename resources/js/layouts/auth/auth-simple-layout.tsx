import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh items-center justify-center overflow-hidden bg-background p-6 text-foreground md:p-10">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(124,58,237,0.25),transparent_45%),radial-gradient(circle_at_bottom_left,rgba(39,104,216,0.18),transparent_40%)]" />

            <div className="relative w-full max-w-md">
                <div className="rounded-2xl border border-border-light bg-card/95 p-6 shadow-card backdrop-blur-md md:p-8">
                    <div className="mb-8 flex flex-col items-center gap-4 text-center">
                        <Link
                            href={home()}
                            className="inline-flex items-center"
                        >
                            <img
                                src="/logo.png"
                                alt="Nuvelo"
                                className="h-12 w-auto"
                            />
                        </Link>

                        <div className="space-y-2">
                            <h1 className="text-xl font-semibold tracking-tight text-white md:text-2xl">
                                {title}
                            </h1>
                            <p className="text-sm text-gray-300">
                                {description}
                            </p>
                        </div>
                    </div>

                    <div className="[&_.text-muted-foreground]:text-gray-400 [&_a]:text-primary [&_a:hover]:text-[var(--color-primary-light)] [&_button[type='submit']]:bg-primary [&_button[type='submit']]:text-white [&_button[type='submit']:hover]:bg-[var(--color-primary-light)] [&_input]:border-border-light [&_input]:bg-secondary [&_input]:text-white [&_input]:placeholder:text-gray-500 [&_label]:text-gray-200">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}

