import { FormEvent, useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { ApiError } from '@hris/api-client';
import { useAuth } from '../../lib/AuthContext';

export function LoginPage() {
  const { user, login } = useAuth();
  const [email, setEmail] = useState('admin@example.com');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (user) return <Navigate to="/" replace />;

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(email, password);
    } catch (err) {
      if (err instanceof ApiError && err.status === 429) {
        setError(String((err.body as { message?: string })?.message ?? 'Too many login attempts. Please try again in a minute.'));
      } else {
        setError('Invalid credentials.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="center-screen">
      <form className="card login-card" onSubmit={handleSubmit}>
        <h1>HRIS</h1>
        <p className="muted">Sign in to continue</p>
        <label>
          Email
          <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required />
        </label>
        <label>
          Password
          <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" required />
        </label>
        {error && <div className="error-text">{error}</div>}
        <button type="submit" disabled={submitting}>
          {submitting ? 'Signing in…' : 'Sign in'}
        </button>
        <p className="muted small">
          <Link to="/forgot-password">Forgot password?</Link>
        </p>
        <p className="muted small">
          Demo: admin@example.com / hr.manager@example.com / people.manager@example.com / casey.nguyen@example.com
          <br />
          password: <code>password</code>
        </p>
      </form>
    </div>
  );
}
