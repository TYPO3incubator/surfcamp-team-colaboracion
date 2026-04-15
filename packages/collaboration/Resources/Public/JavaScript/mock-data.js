/**
 * Mock data for presence indicators during development.
 * Will be replaced by real API calls when the data layer is ready.
 */
export const mockPresenceData = {
  currentUser: {
    uid: 1,
    displayName: 'Admin',
    avatarUrl: null,
    module: 'layout',
    editingElement: null,
    activeField: null,
    activeSince: 45,
    idle: false,
  },
  pageUsers: [
    {
      uid: 1,
      displayName: 'Admin',
      avatarUrl: null,
      module: 'layout',
      editingElement: null,
      activeField: null,
      activeSince: 45,
      idle: false,
    },
    {
      uid: 2,
      displayName: 'Nils Kreutzmann',
      avatarUrl: null,
      module: 'layout',
      editingElement: 'CE 46',
      activeField: 'bodytext',
      activeSince: 12,
      idle: false,
    },
    {
      uid: 3,
      displayName: 'Carsten Bleicker',
      avatarUrl: null,
      module: 'records',
      editingElement: 'CE 50',
      activeField: 'header',
      activeSince: 3,
      idle: true,
    },
  ],
  editingRecords: {
    'tt_content:46': {
      users: [
        {
          uid: 2,
          displayName: 'Nils Kreutzmann',
          avatarUrl: null,
          activeField: 'bodytext',
          idle: false,
        },
      ],
      count: 1,
    },
    'tt_content:50': {
      users: [
        {
          uid: 2,
          displayName: 'Nils Kreutzmann',
          avatarUrl: null,
          activeField: 'bodytext',
          idle: false,
        },
        {
          uid: 3,
          displayName: 'Carsten Bleicker',
          avatarUrl: null,
          activeField: 'header',
          idle: false,
        },
      ],
      count: 2,
    },
  },
};
