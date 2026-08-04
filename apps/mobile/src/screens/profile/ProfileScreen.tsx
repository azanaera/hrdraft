import { useEffect, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import type { DocumentRecord, Employment } from '@hris/shared-types';
import { colors, spacing } from '@hris/ui-tokens';
import { api } from '../../lib/apiClient';
import { useAuth } from '../../lib/AuthContext';

export function ProfileScreen() {
  const { user, logout } = useAuth();
  const [employment, setEmployment] = useState<Employment | null>(null);
  const [documents, setDocuments] = useState<DocumentRecord[]>([]);

  useEffect(() => {
    if (!user?.employment_id) return;
    api.getEmployee(user.employment_id).then(setEmployment);
    api.listDocuments(user.employment_id).then((res) => setDocuments(res.data));
  }, [user?.employment_id]);

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.name}>
          {employment?.person.first_name} {employment?.person.last_name}
        </Text>
        <Text style={styles.role}>{user?.role.replace('_', ' ')}</Text>

        <View style={styles.card}>
          <Row label="Employee #" value={employment?.employee_number} />
          <Row label="Department" value={employment?.current_assignment?.department} />
          <Row label="Location" value={employment?.current_assignment?.location} />
          <Row label="Position" value={employment?.current_assignment?.position} />
          <Row label="Hire date" value={employment?.hire_date} />
        </View>

        <Text style={styles.sectionTitle}>My documents</Text>
        <View style={styles.card}>
          {documents.map((d) => (
            <Row key={d.id} label={d.title} value={d.category ?? undefined} />
          ))}
          {documents.length === 0 && <Text style={styles.muted}>No documents yet.</Text>}
        </View>

        <Pressable style={styles.logoutButton} onPress={() => logout()}>
          <Text style={styles.logoutText}>Log out</Text>
        </Pressable>
      </ScrollView>
    </SafeAreaView>
  );
}

function Row({ label, value }: { label: string; value?: string | null }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value ?? '—'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  content: { padding: spacing.lg },
  name: { fontSize: 24, fontWeight: '800' },
  role: { color: colors.textMuted, textTransform: 'capitalize', marginBottom: spacing.md },
  sectionTitle: { fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm },
  card: {
    backgroundColor: colors.surfaceMuted,
    borderRadius: 10,
    padding: spacing.md,
  },
  row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 },
  rowLabel: { color: colors.textMuted },
  rowValue: { fontWeight: '600' },
  muted: { color: colors.textMuted },
  logoutButton: { marginTop: spacing.xl, alignItems: 'center', padding: spacing.md },
  logoutText: { color: colors.danger, fontWeight: '700' },
});
