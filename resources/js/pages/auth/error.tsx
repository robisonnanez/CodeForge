import { Head, Link } from '@inertiajs/react';

export default function AuthErrorPage() {
    return (
        <>
            <Head title="Error" />
            <div className="atlantis-theme flex min-h-screen items-center justify-center bg-[#0b1136] px-4">
                <div className="w-full max-w-xl rounded-2xl border border-white/10 bg-[#1d254f] p-10 text-center shadow-2xl">
                    <p className="text-6xl font-bold text-fuchsia-400">500</p>
                    <h1 className="mt-4 text-3xl font-semibold text-white">
                        Unexpected Error
                    </h1>
                    <p className="mt-3 text-slate-300">
                        Something went wrong while processing your request.
                        Please try again or return to login.
                    </p>
                    <div className="mt-8 flex flex-wrap justify-center gap-3">
                        <Link
                            href="/login"
                            className="rounded-lg bg-fuchsia-500 px-5 py-2 font-medium text-white hover:bg-fuchsia-400"
                        >
                            Return to login
                        </Link>
                        <Link
                            href="/"
                            className="rounded-lg border border-white/20 px-5 py-2 font-medium text-slate-200 hover:bg-white/10"
                        >
                            Go home
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
