import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../lib/AuthContext';
import { LoginScreen } from '../screens/auth/LoginScreen';
import { MainTabs } from './MainTabs';
import { colors } from '@hris/ui-tokens';
import { ActivityIndicator, View } from 'react-native';

const Stack = createNativeStackNavigator();

export function RootNavigator() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
        <ActivityIndicator color={colors.primary} size="large" />
      </View>
    );
  }

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {user ? <Stack.Screen name="Main" component={MainTabs} /> : <Stack.Screen name="Login" component={LoginScreen} />}
    </Stack.Navigator>
  );
}
