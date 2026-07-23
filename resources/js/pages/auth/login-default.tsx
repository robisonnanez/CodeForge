import { Form, Head, Link } from '@inertiajs/react';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean };

export default function LoginDefault({ canResetPassword, status }: Props) {
    return (
        <>
            <Head title="Log in" />
            <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
                <div className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 className="text-2xl font-semibold text-slate-900">
                        Log in
                    </h1>
                    <p className="mt-1 text-sm text-slate-600">
                        Access your account with your credentials.
                    </p>

                    {status && (
                        <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {status}
                        </div>
                    )}

                    <Form {...store.form()} className="mt-6 space-y-4">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">
                                        Email
                                    </label>
                                    <input
                                        name="email"
                                        type="email"
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 outline-none focus:border-slate-500"
                                        placeholder="you@example.com"
                                    />
                                    {errors.email && (
                                        <p className="mt-1 text-xs text-rose-600">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">
                                        Password
                                    </label>
                                    <input
                                        name="password"
                                        type="password"
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 outline-none focus:border-slate-500"
                                        placeholder="Your password"
                                    />
                                    {errors.password && (
                                        <p className="mt-1 text-xs text-rose-600">
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center justify-between gap-3">
                                    <label className="flex items-center gap-2 text-sm text-slate-700">
                                        <input
                                            name="remember"
                                            value="1"
                                            type="checkbox"
                                            className="h-4 w-4 rounded border-slate-300"
                                        />
                                        Remember me
                                    </label>
                                    {canResetPassword && (
                                        <a
                                            href={request().url}
                                            className="text-sm text-slate-700 hover:text-slate-900"
                                        >
                                            Forgot password?
                                        </a>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Signing in...' : 'Sign in'}
                                </button>

                                <p className="text-center text-xs text-slate-500">
                                    Need help?{' '}
                                    <Link
                                        href="/auth/error"
                                        className="text-slate-700 hover:text-slate-900"
                                    >
                                        Open support page
                                    </Link>
                                </p>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
