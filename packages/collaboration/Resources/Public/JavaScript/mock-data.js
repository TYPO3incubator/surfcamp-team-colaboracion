/**
 * Mock data for presence indicators during development.
 * Will be replaced by real API calls when the data layer is ready.
 */
export const mockPresenceData = {
  currentUser: {
    uid: 1,
    displayName: 'Admin',
    avatarUrl: null,
  },
  pageUsers: [
    {
      uid: 1,
      displayName: 'Admin',
      avatarUrl: null,
    },
    {
      uid: 2,
      displayName: 'Nils Kreutzmann',
      avatarUrl: null,
    },
    {
      uid: 3,
      displayName: 'Carsten Bleicker',
      avatarUrl: null,
    },
  ],
  editingRecords: {
    'tt_content:5': {
      users: [
        { uid: 2, displayName: 'Nils Kreutzmann', avatarUrl: null },
      ],
      count: 1,
    },
    'tt_content:12': {
      users: [
        { uid: 2, displayName: 'Nils Kreutzmann', avatarUrl: null },
        { uid: 3, displayName: 'Carsten Bleicker', avatarUrl: null },
      ],
      count: 2,
    },
    'tt_content:18': {
      users: [
        { uid: 2, displayName: 'Nils Kreutzmann', avatarUrl: null },
        { uid: 3, displayName: 'Carsten Bleicker', avatarUrl: null },
        { uid: 4, displayName: 'Julian Schuierer', avatarUrl: null },
      ],
      count: 3,
    },
  },
};
