import { FormEvent, useEffect, useState } from 'react';
import type { EmployeeEvent } from '@hris/shared-types';
import { api } from '../../../lib/apiClient';

export function TimelineTab({ personId }: { personId: number }) {
  const [events, setEvents] = useState<EmployeeEvent[]>([]);
  const [note, setNote] = useState('');
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.getTimeline(personId).then((res) => setEvents(res.data));
  }

  useEffect(reload, [personId]);

  async function addNote(e: FormEvent) {
    e.preventDefault();
    if (!note.trim()) return;
    setSubmitting(true);
    try {
      await api.addNote(personId, note);
      setNote('');
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div>
      <form className="card" onSubmit={addNote}>
        <label>
          Add a note
          <textarea value={note} onChange={(e) => setNote(e.target.value)} rows={3} />
        </label>
        <button type="submit" disabled={submitting}>
          {submitting ? 'Saving…' : 'Add note'}
        </button>
      </form>

      <ul className="timeline">
        {events.map((event) => (
          <li key={event.id} className="timeline-item">
            <div className="timeline-date">{event.event_date}</div>
            <div className="timeline-body">
              <span className="timeline-type">{event.event_type.replace(/_/g, ' ')}</span>
              <p>{event.summary}</p>
              {event.actor && <span className="muted small">by {event.actor}</span>}
            </div>
          </li>
        ))}
        {events.length === 0 && <p className="muted">No timeline events yet.</p>}
      </ul>
    </div>
  );
}
