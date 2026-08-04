import { useEffect, useState } from 'react';
import { FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import type { TimeOffRequest } from '@hris/shared-types';
import { colors, spacing } from '@hris/ui-tokens';
import { api } from '../../lib/apiClient';

/**
 * Manager-facing approvals + team lookup. Deliberately kept to time-off
 * decisions and a roster view — comp/ATS/onboarding admin stay web-only
 * for the scoped mobile v1.
 */
export function TeamScreen() {
  const [requests, setRequests] = useState<TimeOffRequest[]>([]);

  function reload() {
    api.listTimeOffRequests({ status: 'pending' }).then((res) => setRequests(res.data));
  }

  useEffect(reload, []);

  async function decide(id: number, decision: 'approve' | 'deny') {
    await api.decideTimeOffRequest(id, decision);
    reload();
  }

  return (
    <SafeAreaView style={styles.container} edges={['bottom']}>
      <Text style={styles.header}>Pending approvals</Text>
      <FlatList
        data={requests}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.lg }}
        renderItem={({ item }) => (
          <View style={styles.card}>
            <Text style={styles.name}>{item.employee_name}</Text>
            <Text style={styles.muted}>
              {item.start_date} – {item.end_date} · {item.hours_requested}h
            </Text>
            <View style={styles.actions}>
              <Pressable style={[styles.actionButton, styles.approve]} onPress={() => decide(item.id, 'approve')}>
                <Text style={styles.actionText}>Approve</Text>
              </Pressable>
              <Pressable style={[styles.actionButton, styles.deny]} onPress={() => decide(item.id, 'deny')}>
                <Text style={styles.actionText}>Deny</Text>
              </Pressable>
            </View>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.muted}>No pending requests from your team.</Text>}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  header: { fontWeight: '700', fontSize: 16, padding: spacing.lg, paddingBottom: 0 },
  card: { backgroundColor: colors.surfaceMuted, borderRadius: 10, padding: spacing.md, marginBottom: spacing.sm },
  name: { fontWeight: '700' },
  muted: { color: colors.textMuted, marginTop: 2 },
  actions: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm },
  actionButton: { borderRadius: 6, paddingVertical: 6, paddingHorizontal: 14 },
  approve: { backgroundColor: colors.success },
  deny: { backgroundColor: colors.danger },
  actionText: { color: '#fff', fontWeight: '700', fontSize: 12 },
});
