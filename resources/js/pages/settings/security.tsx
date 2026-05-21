import { Form, Head } from '@inertiajs/react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { edit } from '@/routes/security';

export default function Security() {
  return (
    <>
      <Head title="Security settings" />
      <Form {...SecurityController.update.form()}>
        {() => (
          <>
            <input name="current_password" type="password" />
            <input name="password" type="password" />
            <input name="password_confirmation" type="password" />
            <button type="submit">Save password</button>
          </>
        )}
      </Form>
    </>
  );
}

Security.layout = { breadcrumbs: [{ title: 'Security settings', href: edit() }] };
