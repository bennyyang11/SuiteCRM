import { configureStore } from '@reduxjs/toolkit'
import { setupListeners } from '@reduxjs/toolkit/query'
import { authSlice } from './slices/authSlice'
import { uiSlice } from './slices/uiSlice'
import { manufacturingApi } from './api/manufacturingApi'
import { persistStore, persistReducer } from 'redux-persist'
import storage from 'redux-persist/lib/storage'

// Persist configuration for auth state
const authPersistConfig = {
  key: 'auth',
  storage,
  whitelist: ['token', 'user', 'isAuthenticated']
}

// Configure the store with all slices and API
export const store = configureStore({
  reducer: {
    auth: persistReducer(authPersistConfig, authSlice.reducer),
    ui: uiSlice.reducer,
    [manufacturingApi.reducerPath]: manufacturingApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST', 'persist/REHYDRATE'],
      },
    }).concat(manufacturingApi.middleware),
  devTools: process.env.NODE_ENV !== 'production',
})

// Enable refetch on focus/reconnect behaviors
setupListeners(store.dispatch)

// Export types
export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch

// Export persistor for Redux Persist
export const persistor = persistStore(store)
