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
    <div className="account-page">
      <div className="container-fluid p-0">
        <div className="row align-items-center justify-content-center g-0 px-3 py-3 vh-100">
          <div className="col-xl-4">
            <div className="row">
              <div className="col-md-9 mx-auto">
                <div className="card">
                  <div className="card-body p-lg-4">
                    <div className="mb-4 text-center">
                      <h1 className="fs-24 fw-semibold text-dark mb-0">HRIS</h1>
                    </div>

                    <div className="auth-title-section mb-4 text-center">
                      <h3 className="text-dark fw-semibold mb-2 fs-20">Welcome back</h3>
                      <p className="text-muted fs-14 mb-0">Sign in to continue</p>
                    </div>

                    <form onSubmit={handleSubmit}>
                      <div className="form-group mb-3">
                        <label htmlFor="email" className="form-label">
                          Email
                        </label>
                        <input
                          id="email"
                          className="form-control"
                          type="email"
                          value={email}
                          onChange={(e) => setEmail(e.target.value)}
                          required
                        />
                      </div>

                      <div className="form-group mb-3">
                        <label htmlFor="password" className="form-label">
                          Password
                        </label>
                        <input
                          id="password"
                          className="form-control"
                          type="password"
                          value={password}
                          onChange={(e) => setPassword(e.target.value)}
                          required
                        />
                      </div>

                      {error && (
                        <div className="alert alert-danger fs-14 py-2" role="alert">
                          {error}
                        </div>
                      )}

                      <div className="form-group mb-0">
                        <div className="d-grid">
                          <button className="btn btn-primary fw-semibold" type="submit" disabled={submitting}>
                            {submitting ? 'Signing in…' : 'Sign in'}
                          </button>
                        </div>
                      </div>
                    </form>

                    <div className="text-center mt-3">
                      <Link to="/forgot-password" className="text-muted fs-14">
                        Forgot password?
                      </Link>
                    </div>

                    <div className="text-center text-muted mt-4 pt-2 border-top fs-13">
                      <p className="mb-1 mt-3">
                        Demo: admin@example.com &middot; hr.manager@example.com &middot; people.manager@example.com &middot;
                        casey.nguyen@example.com
                      </p>
                      <p className="mb-0">
                        password: <code>password</code>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
