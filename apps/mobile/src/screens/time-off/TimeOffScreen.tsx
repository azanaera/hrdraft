import { useEffect, useState } from 'react';
import { FlatList, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import type { TimeOffPolicy, TimeOffRequest } from '@hris/shared-types';
import { colors, spacing } from '@hris/ui-tokens';
import { api } from '../../lib/apiClient';
import { useAuth } from '../../lib/AuthContext';

export function TimeOffScreen() {
  const { user } = useAuth();
  const [requests, setRequests] = useState<TimeOffRequest[]>([]);
  const [policies, setPolicies] = useState<TimeOffPolicy[]>([]);
  const [hours, setHours] = useState('8');
  const [submitting, setSubmitting] = useState(false);

  function reload() {
    api.listTimeOffRequests().then((res) => setRequests(res.data));
  }

  useEffect(() => {
    reload();
    api.listTimeOffPolicies().then(setPolicies);
  }, []);

  async function submitRequest() {
    if (!user?.employment_id || policies.length === 0) return;
    setSubmitting(true);
    const today = new Date().toISOString().slice(0, 10);
    try {
      await api.submitTimeOffRequest({
        employment_id: user.employment_id,
        policy_id: policies[0].id,
        start_date: today,
        end_date: today,
        hours_requested: Number(hours),
      });
      reload();
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
      <View style={styles.form}>
        <Text style={styles.label}>Quick request: hours for today ({policies[0]?.name ?? 'PTO'})</Text>
        <View style={styles.row}>
          <TextInput style={styles.input} keyboardType="numeric" value={hours} onChangeText={setHours} />
          <Pressable style={styles.button} onPress={submitRequest} disabled={submitting}>
            <Text style={styles.buttonText}>{submitting ? 'Submitting…' : 'Request'}</Text>
          </Pressable>
        </View>
      </View>

      <FlatList
        data={requests}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.lg }}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>
              {item.start_date} – {item.end_date} ({item.hours_requested}h)
            </Text>
            <Text style={[styles.status, statusStyle(item.status)]}>{item.status}</Text>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.muted}>No time off requests yet.</Text>}
      />
    </SafeAreaView>
  );
}

function statusStyle(status: string) {
  if (status === 'approved') return { color: colors.success };
  if (status === 'denied') return { color: colors.danger };
  return { color: colors.warning };
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  form: { padding: spacing.lg, borderBottomWidth: 1, borderBottomColor: colors.border },
  label: { fontWeight: '600', marginBottom: spacing.sm },
  row: { flexDirection: 'row', gap: spacing.sm },
  input: { flex: 1, borderWidth: 1, borderColor: colors.border, borderRadius: 8, padding: spacing.sm },
  button: { backgroundColor: colors.primary, borderRadius: 8, padding: spacing.sm, justifyContent: 'center', paddingHorizontal: spacing.md },
  buttonText: { color: '#fff', fontWeight: '700' },
  card: { backgroundColor: colors.surfaceMuted, borderRadius: 10, padding: spacing.md, marginBottom: spacing.sm },
  cardTitle: { fontWeight: '600' },
  status: { marginTop: 4, textTransform: 'capitalize', fontWeight: '700' },
  muted: { color: colors.textMuted, textAlign: 'center', marginTop: spacing.lg },
});
