import { FormEvent, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ApiError } from '@hris/api-client';
import { api } from '../../lib/apiClient';

export function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get('token') ?? '';
  const email = searchParams.get('email') ?? '';
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await api.resetPassword({ token, email, password, password_confirmation: passwordConfirmation });
      navigate('/login');
    } catch (err) {
      setError(err instanceof ApiError ? 'Could not reset password — the link may have expired.' : 'Something went wrong.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="center-screen">
      <form className="card login-card" onSubmit={handleSubmit}>
        <h1>Reset password</h1>
        <p className="muted small">for {email}</p>
        <label>
          New password
          <input type="password" required minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} />
        </label>
        <label>
          Confirm password
          <input type="password" required value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} />
        </label>
        {error && <div className="error-text">{error}</div>}
        <button type="submit" disabled={submitting}>
          {submitting ? 'Resetting…' : 'Reset password'}
        </button>
        <p className="muted small">
          <Link to="/login">Back to sign in</Link>
        </p>
      </form>
    </div>
  );
}
