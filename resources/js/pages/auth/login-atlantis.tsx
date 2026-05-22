import { Form, Head, Link } from '@inertiajs/react';
import { Password } from 'primereact/password';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean };

export default function LoginAtlantis({ canResetPassword, status }: Props) {
  return (
    <>
      <Head title="Login" />
      <div className="atlantis-theme min-h-screen bg-[#0b1136] px-4 py-10">
        <div className="mx-auto grid w-full max-w-6xl overflow-hidden rounded-2xl border border-white/10 bg-[#1d254f] shadow-2xl lg:grid-cols-2">
          <div className="hidden flex-col justify-between bg-[radial-gradient(circle_at_top,_#2f3b75,_#151b3b)] p-10 lg:flex">
            <div>
              <p className="text-4xl font-bold text-white">Atlantis</p>
              <p className="mt-3 text-sm text-slate-300">Repobase Control Center</p>
            </div>
            <div>
              <h2 className="text-3xl font-semibold text-white">Welcome back</h2>
              <p className="mt-3 text-slate-300">Sign in to continue managing modules, permissions, and your dashboard.</p>
            </div>
          </div>

          <div className="p-6 sm:p-10">
            <h1 className="text-3xl font-semibold text-white">Log in</h1>
            <p className="mt-2 text-sm text-slate-300">Use your account credentials to access the platform.</p>

            {status && <div className="mt-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{status}</div>}

            <Form {...store.form()} className="mt-6 space-y-5">
              {({ processing, errors }) => (
                <>
                  <div>
                    <label className="mb-2 block text-sm font-medium text-slate-200">Email</label>
                    <input name="email" type="email" className="w-full rounded-lg border border-white/10 bg-[#2a325d] px-3 py-2 text-white outline-none ring-0 placeholder:text-slate-400 focus:border-fuchsia-400" placeholder="you@example.com" />
                    {errors.email && <p className="mt-1 text-xs text-rose-300">{errors.email}</p>}
                  </div>

                  <div>
                    <label className="mb-2 block text-sm font-medium text-slate-200">Password</label>
                    <Password
                      inputId="password"
                      name="password"
                      feedback={false}
                      toggleMask
                      className="atlantis-password-field"
                      inputClassName="rounded-lg border border-white/10 bg-[#2a325d] px-3 py-2 text-white outline-none ring-0 placeholder:text-slate-400 focus:border-fuchsia-400"
                      placeholder="Your password"
                    />
                    {errors.password && <p className="mt-1 text-xs text-rose-300">{errors.password}</p>}
                  </div>

                  <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm text-slate-300">
                      <input name="remember" value="1" type="checkbox" className="h-4 w-4 rounded border border-white/20 bg-[#2a325d]" />
                      Remember me
                    </label>
                    {canResetPassword && <a href={request().url} className="text-sm text-fuchsia-300 hover:text-fuchsia-200">Forgot password?</a>}
                  </div>

                  <button type="submit" disabled={processing} className="w-full rounded-lg bg-fuchsia-500 px-4 py-2.5 font-semibold text-white transition hover:bg-fuchsia-400 disabled:cursor-not-allowed disabled:opacity-60">
                    {processing ? 'Signing in...' : 'Sign in'}
                  </button>

                  <p className="text-center text-xs text-slate-400">
                    Need help? <Link href="/auth/error" className="text-fuchsia-300 hover:text-fuchsia-200">Open support page</Link>
                  </p>
                </>
              )}
            </Form>
          </div>
        </div>
      </div>
    </>
  );
}
