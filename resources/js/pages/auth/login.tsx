import { Form, Head } from '@inertiajs/react';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = { status?: string; canResetPassword: boolean; canRegister: boolean };

export default function Login({ canResetPassword }: Props) {
  return (
    <>
      <Head title="Log in" />
      <Form {...store.form()} className="flex flex-col gap-4">
        {() => (
          <>
            <input name="email" type="email" />
            <input name="password" type="password" />
            {canResetPassword && <a href={request().url}>Forgot password?</a>}
            <button type="submit">Log in</button>
          </>
        )}
      </Form>
    </>
  );
}
