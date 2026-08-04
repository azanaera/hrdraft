import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { colors } from '@hris/ui-tokens';
import { useAuth } from '../lib/AuthContext';
import { ProfileScreen } from '../screens/profile/ProfileScreen';
import { TimeOffScreen } from '../screens/time-off/TimeOffScreen';
import { TeamScreen } from '../screens/team/TeamScreen';

const Tab = createBottomTabNavigator();

export function MainTabs() {
  const { user } = useAuth();
  const isManager = user?.role === 'people_manager' || user?.role === 'hr_manager' || user?.role === 'admin';

  return (
    <Tab.Navigator screenOptions={{ tabBarActiveTintColor: colors.primary, headerTitleAlign: 'center' }}>
      <Tab.Screen name="Profile" component={ProfileScreen} options={{ title: 'My Profile' }} />
      <Tab.Screen name="TimeOff" component={TimeOffScreen} options={{ title: 'Time Off' }} />
      {isManager && <Tab.Screen name="Team" component={TeamScreen} options={{ title: 'My Team' }} />}
    </Tab.Navigator>
  );
}
