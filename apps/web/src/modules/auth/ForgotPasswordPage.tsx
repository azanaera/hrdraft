import { FormEvent, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../lib/apiClient';

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [sent, setSent] = useState(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.forgotPassword(email);
      setSent(true);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="center-screen">
      <form className="card login-card" onSubmit={handleSubmit}>
        <h1>Forgot password</h1>
        {sent ? (
          <p>If that email is registered, a reset link has been sent (check the backend log locally — no real SMTP is configured).</p>
        ) : (
          <>
            <label>
              Email
              <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
            </label>
            <button type="submit" disabled={submitting}>
              {submitting ? 'Sending…' : 'Send reset link'}
            </button>
          </>
        )}
        <p className="muted small">
          <Link to="/login">Back to sign in</Link>
        </p>
      </form>
    </div>
  );
}
