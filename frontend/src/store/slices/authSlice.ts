import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import type { RootState } from '../index'

interface User {
  id: string
  username: string
  email: string
  firstName: string
  lastName: string
  role: 'admin' | 'manager' | 'sales_rep' | 'client'
  permissions: string[]
  clientTier?: 'premium' | 'standard' | 'basic'
}

interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  sessionTimeout: number | null
}

const initialState: AuthState = {
  user: null,
  token: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,
  sessionTimeout: null,
}

export const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    loginStart: (state) => {
      state.isLoading = true
      state.error = null
    },
    loginSuccess: (state, action: PayloadAction<{ user: User; token: string; expiresIn: number }>) => {
      state.isLoading = false
      state.isAuthenticated = true
      state.user = action.payload.user
      state.token = action.payload.token
      state.sessionTimeout = Date.now() + (action.payload.expiresIn * 1000)
      state.error = null
    },
    loginFailure: (state, action: PayloadAction<string>) => {
      state.isLoading = false
      state.isAuthenticated = false
      state.user = null
      state.token = null
      state.error = action.payload
      state.sessionTimeout = null
    },
    logout: (state) => {
      state.user = null
      state.token = null
      state.isAuthenticated = false
      state.error = null
      state.sessionTimeout = null
    },
    updateUser: (state, action: PayloadAction<Partial<User>>) => {
      if (state.user) {
        state.user = { ...state.user, ...action.payload }
      }
    },
    refreshToken: (state, action: PayloadAction<{ token: string; expiresIn: number }>) => {
      state.token = action.payload.token
      state.sessionTimeout = Date.now() + (action.payload.expiresIn * 1000)
    },
    clearError: (state) => {
      state.error = null
    },
    setSessionTimeout: (state, action: PayloadAction<number>) => {
      state.sessionTimeout = action.payload
    },
  },
})

// Action creators
export const {
  loginStart,
  loginSuccess,
  loginFailure,
  logout,
  updateUser,
  refreshToken,
  clearError,
  setSessionTimeout,
} = authSlice.actions

// Selectors
export const selectAuth = (state: RootState) => state.auth
export const selectUser = (state: RootState) => state.auth.user
export const selectIsAuthenticated = (state: RootState) => state.auth.isAuthenticated
export const selectUserRole = (state: RootState) => state.auth.user?.role
export const selectUserPermissions = (state: RootState) => state.auth.user?.permissions || []
export const selectClientTier = (state: RootState) => state.auth.user?.clientTier
export const selectAuthToken = (state: RootState) => state.auth.token
export const selectIsAuthLoading = (state: RootState) => state.auth.isLoading
export const selectAuthError = (state: RootState) => state.auth.error
export const selectSessionTimeout = (state: RootState) => state.auth.sessionTimeout

// Permission checker
export const selectHasPermission = (permission: string) => (state: RootState) => {
  const permissions = selectUserPermissions(state)
  return permissions.includes(permission) || permissions.includes('admin:all')
}

export default authSlice.reducer
